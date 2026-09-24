<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Api\ListsApi;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactIdentifierType;
use PHPUnit\Framework\TestCase;
use Tests\Odiseo\SyliusBrevoPlugin\Double\BrevoFixture;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;

final class ListsApiTest extends TestCase
{
    private FakeBrevoHttpClient $client;

    private ListsApi $api;

    private Credentials $credentials;

    protected function setUp(): void
    {
        $this->client = new FakeBrevoHttpClient();
        $this->api = new ListsApi($this->client);
        $this->credentials = new Credentials('key');
    }

    public function testItListsLists(): void
    {
        $this->client->queue('GET', '/contacts/lists', BrevoFixture::response('contact_lists'));

        $lists = iterator_to_array($this->api->lists($this->credentials), false);

        self::assertCount(1, $lists);
        self::assertSame(2, $lists[0]->id);
        self::assertSame('Your first list', $lists[0]->name);
        self::assertSame(1, $lists[0]->folderId);
        self::assertSame(['limit' => 50, 'offset' => 0, 'sort' => 'asc'], $this->client->lastRequest()?->query);
    }

    public function testItWalksEveryPage(): void
    {
        $page = static fn (int $from, int $size): array => array_map(
            static fn (int $id): array => ['id' => $id, 'name' => 'List ' . $id],
            $size > 0 ? range($from, $from + $size - 1) : [],
        );
        $this->client->queue('GET', '/contacts/lists', new BrevoResponse(200, ['lists' => $page(1, 50), 'count' => 70]));
        $this->client->queue('GET', '/contacts/lists', new BrevoResponse(200, ['lists' => $page(51, 20), 'count' => 70]));

        $lists = iterator_to_array($this->api->lists($this->credentials), false);

        self::assertCount(70, $lists);
        self::assertSame(70, $lists[69]->id);
        self::assertSame(50, $this->client->lastRequest()?->query['offset']);
        self::assertCount(2, $this->client->requests());
    }

    public function testAnEmptyAccountHasNoLists(): void
    {
        $this->client->queue('GET', '/contacts/lists', new BrevoResponse(200, ['count' => 0]));

        self::assertSame([], iterator_to_array($this->api->lists($this->credentials), false));
    }

    public function testItCreatesAList(): void
    {
        $this->client->queue('POST', '/contacts/lists', new BrevoResponse(201, ['id' => 5]));

        self::assertSame(5, $this->api->createList($this->credentials, 'Newsletter', 1));
        self::assertSame(['name' => 'Newsletter', 'folderId' => 1], $this->client->lastRequest()?->json);
    }

    public function testItAddsContactsInBatches(): void
    {
        $emails = array_map(static fn (int $i): string => sprintf('customer%d@example.com', $i), range(1, 160));
        $this->client->queue('POST', '/contacts/lists/2/contacts/add', BrevoFixture::response('list_contacts_add', 201));
        $this->client->queue('POST', '/contacts/lists/2/contacts/add', new BrevoResponse(201, ['success' => ['customer160@example.com'], 'failure' => []]));

        $result = $this->api->addContacts($this->credentials, 2, ContactIdentifierType::Email, $emails);

        $requests = $this->client->requests('POST', '/contacts/lists/2/contacts/add');
        self::assertCount(2, $requests);
        self::assertSame(array_slice($emails, 0, 150), $requests[0]->json['emails'] ?? null);
        self::assertSame(array_slice($emails, 150), $requests[1]->json['emails'] ?? null);
        self::assertSame(['jeff32@example.com', 'jim56@example.com', 'customer160@example.com'], $result->success);
        self::assertSame(['david@example.com'], $result->failure);
    }

    public function testItRemovesContactsByExtId(): void
    {
        $this->client->queue('POST', '/contacts/lists/2/contacts/remove', new BrevoResponse(201, ['success' => ['customer-7'], 'failure' => []]));

        $result = $this->api->removeContacts($this->credentials, 2, ContactIdentifierType::ExtId, ['customer-7']);

        self::assertSame(['extIds' => ['customer-7']], $this->client->lastRequest()?->json);
        self::assertSame(['customer-7'], $result->success);
    }

    public function testListsDoNotTakePhones(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->api->addContacts($this->credentials, 2, ContactIdentifierType::Phone, ['+15555550101']);
    }

    public function testItListsAndCreatesFolders(): void
    {
        $this->client->queue('GET', '/contacts/folders', BrevoFixture::response('contact_folders'));
        $this->client->queue('POST', '/contacts/folders', new BrevoResponse(201, ['id' => 9]));

        $folders = iterator_to_array($this->api->folders($this->credentials), false);

        self::assertSame('Your first folder', $folders[0]->name);
        self::assertSame(9, $this->api->createFolder($this->credentials, 'Sylius'));
        self::assertSame(['name' => 'Sylius'], $this->client->lastRequest()?->json);
    }
}
