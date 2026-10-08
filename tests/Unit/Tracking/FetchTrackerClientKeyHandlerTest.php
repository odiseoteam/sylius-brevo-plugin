<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Tracking;

use Doctrine\Persistence\ObjectManager;
use Odiseo\SyliusBrevoPlugin\Client\Api\AccountApi;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use Odiseo\SyliusBrevoPlugin\Tracking\Message\FetchTrackerClientKey;
use Odiseo\SyliusBrevoPlugin\Tracking\MessageHandler\FetchTrackerClientKeyHandler;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\Channel;

final class FetchTrackerClientKeyHandlerTest extends TestCase
{
    private FakeBrevoHttpClient $client;

    private ChannelConfiguration $configuration;

    protected function setUp(): void
    {
        $this->client = new FakeBrevoHttpClient();
        $this->client->queue('GET', '/account', new BrevoResponse(200, [
            'email' => 'shop@example.com',
            'marketingAutomation' => ['key' => 'rdhr1ilkhrmf1nm1xjx7n1vz', 'enabled' => true],
        ]));

        $this->configuration = new ChannelConfiguration();
        $this->configuration->setModules(['tracking']);
    }

    public function testAnEmptyKeyIsFilledFromTheAccount(): void
    {
        $this->handle();

        self::assertSame('rdhr1ilkhrmf1nm1xjx7n1vz', $this->configuration->getTrackerClientKey());
    }

    public function testAKeyTypedInTheAdminIsKept(): void
    {
        $this->configuration->setTrackerClientKey('typedbyhand');

        $this->handle();

        self::assertSame('typedbyhand', $this->configuration->getTrackerClientKey());
        self::assertSame([], $this->client->requests());
    }

    public function testNothingIsFetchedWithoutTheTrackingModule(): void
    {
        $this->configuration->setModules([]);

        $this->handle();

        self::assertNull($this->configuration->getTrackerClientKey());
        self::assertSame([], $this->client->requests());
    }

    private function handle(): void
    {
        $channels = $this->createStub(ChannelRepositoryInterface::class);
        $channels->method('findOneByCode')->willReturn(new Channel());
        $configurations = $this->createStub(ChannelConfigurationRepositoryInterface::class);
        $configurations->method('findOneByChannel')->willReturn($this->configuration);
        $provider = $this->createStub(ConfigurationProviderInterface::class);
        $provider->method('getCredentials')->willReturn(new Credentials('key'));

        (new FetchTrackerClientKeyHandler($channels, $configurations, $provider, new AccountApi($this->client), $this->createStub(ObjectManager::class)))(new FetchTrackerClientKey('WEB'));
    }
}
