<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Api\ContactsApi;
use Odiseo\SyliusBrevoPlugin\Client\Api\ProcessesApi;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactData;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use PHPUnit\Framework\TestCase;

final class ImportAndProcessesApiTest extends TestCase
{
    public function testItImportsContactsIntoLists(): void
    {
        $client = new FakeBrevoHttpClient();
        $client->queue('POST', '/contacts/import', new BrevoResponse(202, ['processId' => 78]));

        $processId = (new ContactsApi($client))->import(new Credentials('key'), [
            new ContactData(email: 'carrot@example.com', extId: '7', attributes: ['FIRSTNAME' => 'Carrot']),
            (new ContactData(email: 'nobby@example.com', extId: '8'))->withListIds([9]),
        ], [3]);

        self::assertSame(78, $processId);
        self::assertSame([
            'jsonBody' => [
                ['email' => 'carrot@example.com', 'attributes' => ['FIRSTNAME' => 'Carrot', 'EXT_ID' => '7']],
                ['email' => 'nobby@example.com', 'attributes' => ['EXT_ID' => '8']],
            ],
            'listIds' => [3],
            'updateExistingContacts' => true,
            'emptyContactsAttributes' => false,
            'disableNotification' => true,
        ], $client->lastRequest()?->json);
    }

    public function testItReadsAProcess(): void
    {
        $client = new FakeBrevoHttpClient();
        $client->queue('GET', '/processes/78', new BrevoResponse(200, [
            'id' => 78,
            'name' => 'IMPORTUSER',
            'status' => 'completed',
            'info' => ['import' => ['invalid_emails' => null, 'duplicate_email_id' => 'https://example.com/duplicates.csv']],
        ]));

        $process = (new ProcessesApi($client))->get(new Credentials('key'), 78);

        self::assertSame(78, $process->id);
        self::assertTrue($process->isFinished());
        self::assertSame(['duplicate_email_id' => 'https://example.com/duplicates.csv'], $process->importInfo);
    }

    public function testAQueuedProcessIsNotFinished(): void
    {
        $client = new FakeBrevoHttpClient();
        $client->queue('GET', '/processes/5', new BrevoResponse(200, ['id' => 5, 'status' => 'in_process']));

        self::assertFalse((new ProcessesApi($client))->get(new Credentials('key'), 5)->isFinished());
    }

    public function testListIdsAddUp(): void
    {
        $data = (new ContactData(email: 'a@example.com', listIds: [3]))->withListIds([3, 4]);

        self::assertSame([3, 4], $data->listIds);
    }
}
