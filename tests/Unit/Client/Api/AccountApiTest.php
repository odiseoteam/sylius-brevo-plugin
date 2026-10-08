<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Api\AccountApi;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use PHPUnit\Framework\TestCase;

final class AccountApiTest extends TestCase
{
    public function testItFetchesTheAccount(): void
    {
        $client = new FakeBrevoHttpClient();
        $client->queue('GET', '/account', new BrevoResponse(200, [
            'email' => 'shop@example.com',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'companyName' => 'Example Shop',
            'plan' => [
                ['type' => 'free', 'creditsType' => 'sendLimit', 'credits' => 300],
                'unexpected',
            ],
            'marketingAutomation' => ['key' => 'rdhr1ilkhrmf1nm1xjx7n1vz', 'enabled' => true],
        ]));

        $account = (new AccountApi($client))->getAccount(new Credentials('key'));

        self::assertSame('shop@example.com', $account->email);
        self::assertSame('Example Shop', $account->companyName);
        self::assertSame('Jane', $account->firstName);
        self::assertSame('Doe', $account->lastName);
        self::assertCount(1, $account->plans);
        self::assertSame('free', $account->plans[0]->type);
        self::assertSame('sendLimit', $account->plans[0]->creditsType);
        self::assertSame(300.0, $account->plans[0]->credits);
        self::assertSame('rdhr1ilkhrmf1nm1xjx7n1vz', $account->trackerClientKey);
        self::assertSame('key', $client->lastRequest()?->apiKey);
    }

    public function testItToleratesMissingFields(): void
    {
        $client = new FakeBrevoHttpClient();
        $client->queue('GET', '/account', new BrevoResponse(200, ['email' => 'shop@example.com', 'companyName' => '']));

        $account = (new AccountApi($client))->getAccount(new Credentials('key'));

        self::assertNull($account->companyName);
        self::assertSame([], $account->plans);
        self::assertNull($account->trackerClientKey);
    }

    public function testTheTrackerKeyNeedsMarketingAutomation(): void
    {
        $client = new FakeBrevoHttpClient();
        $client->queue('GET', '/account', new BrevoResponse(200, ['email' => 'shop@example.com', 'marketingAutomation' => ['key' => 'rdhr1ilkhrmf1nm1xjx7n1vz', 'enabled' => false]]));

        self::assertNull((new AccountApi($client))->getAccount(new Credentials('key'))->trackerClientKey);
    }
}
