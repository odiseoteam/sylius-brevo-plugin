<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Integration\Client;

use Odiseo\SyliusBrevoPlugin\Client\Api\AccountApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Http\LoggingBrevoHttpClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;

final class ClientWiringTest extends KernelTestCase
{
    public function testTheClientIsLoggedAndBackedByTheFakeInTests(): void
    {
        self::bootKernel();

        self::assertInstanceOf(LoggingBrevoHttpClient::class, self::getContainer()->get(BrevoHttpClientInterface::class));
        self::assertInstanceOf(FakeBrevoHttpClient::class, self::getContainer()->get('odiseo_brevo.client.http.transport'));
    }

    public function testTheAccountApiGoesThroughTheClient(): void
    {
        self::bootKernel();

        $fake = self::getContainer()->get('odiseo_brevo.client.http.transport');
        self::assertInstanceOf(FakeBrevoHttpClient::class, $fake);
        $fake->queue('GET', '/account', new BrevoResponse(200, ['email' => 'shop@example.com']));

        $accountApi = self::getContainer()->get(AccountApiInterface::class);
        self::assertInstanceOf(AccountApiInterface::class, $accountApi);

        self::assertSame('shop@example.com', $accountApi->getAccount(new Credentials('key'))->email);
    }
}
