<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Configuration;

use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProvider;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;

final class ConfigurationProviderTest extends TestCase
{
    private Channel $channel;

    protected function setUp(): void
    {
        $this->channel = new Channel();
        $this->channel->setCode('WEB');
    }

    public function testItResolvesTheChannelSettings(): void
    {
        $configuration = $this->configuration('own-key');
        $configuration->setSenderName('Shop');
        $configuration->setSenderEmail('shop@example.com');
        $configuration->setModules(['contacts']);

        $settings = $this->provider($configuration, 'default-key')->getSettings($this->channel);

        self::assertNotNull($settings);
        self::assertSame('WEB', $settings->channelCode);
        self::assertSame('own-key', $settings->credentials->apiKey);
        self::assertSame('Shop', $settings->senderName);
        self::assertSame('shop@example.com', $settings->senderEmail);
        self::assertTrue($settings->hasModule('contacts'));
        self::assertFalse($settings->hasModule('sms'));
    }

    public function testItFallsBackToTheDefaultApiKey(): void
    {
        $settings = $this->provider($this->configuration(null), 'default-key')->getSettings($this->channel);

        self::assertSame('default-key', $settings?->credentials->apiKey);
    }

    public function testItHasNoSettingsWithoutAnApiKey(): void
    {
        self::assertNull($this->provider($this->configuration(null), null)->getSettings($this->channel));
        self::assertNull($this->provider($this->configuration(''), '')->getSettings($this->channel));
    }

    public function testItHasNoSettingsForADisabledOrMissingConfiguration(): void
    {
        $disabled = $this->configuration('own-key');
        $disabled->disable();

        self::assertNull($this->provider($disabled, 'default-key')->getSettings($this->channel));
        self::assertNull($this->provider(null, 'default-key')->getSettings($this->channel));
    }

    public function testItGivesCredentialsOfADisabledConfiguration(): void
    {
        $disabled = $this->configuration(null);
        $disabled->disable();

        self::assertSame('default-key', $this->provider($disabled, 'default-key')->getCredentials($disabled)?->apiKey);
    }

    private function configuration(?string $apiKey): ChannelConfiguration
    {
        $configuration = new ChannelConfiguration();
        $configuration->setChannel($this->channel);
        $configuration->setApiKey($apiKey);

        return $configuration;
    }

    private function provider(?ChannelConfiguration $configuration, ?string $defaultApiKey): ConfigurationProvider
    {
        $repository = $this->createStub(ChannelConfigurationRepositoryInterface::class);
        $repository->method('findOneByChannel')->willReturn($configuration);

        return new ConfigurationProvider($repository, $defaultApiKey);
    }
}
