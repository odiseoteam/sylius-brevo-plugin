<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Double;

use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;

final class DummyOrderPlacedHandler
{
    /** @param ChannelRepositoryInterface<ChannelInterface> $channelRepository */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly BrevoHttpClientInterface $client,
    ) {
    }

    public function __invoke(DummyOrderPlaced $message): void
    {
        $channel = $this->channelRepository->findOneByCode($message->getChannelCode());
        $settings = null === $channel ? null : $this->configurationProvider->getSettings($channel);
        if (null === $settings || !$settings->hasModule('dummy')) {
            return;
        }

        $this->client->request($settings->credentials, 'POST', '/dummy/orders', json: ['number' => $message->orderNumber]);
    }
}
