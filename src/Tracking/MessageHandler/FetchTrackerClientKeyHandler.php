<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\MessageHandler;

use Doctrine\Persistence\ObjectManager;
use Odiseo\SyliusBrevoPlugin\Client\Api\AccountApiInterface;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Message\FetchTrackerClientKey;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackingModule;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;

/** Fills an empty tracker client key from the Brevo account; a key typed in the admin is kept. */
final class FetchTrackerClientKeyHandler
{
    /** @param ChannelRepositoryInterface<ChannelInterface> $channelRepository */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ChannelConfigurationRepositoryInterface $configurationRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly AccountApiInterface $accountApi,
        private readonly ObjectManager $configurationManager,
    ) {
    }

    public function __invoke(FetchTrackerClientKey $message): void
    {
        $channel = $this->channelRepository->findOneByCode($message->getChannelCode());
        $configuration = $channel instanceof ChannelInterface ? $this->configurationRepository->findOneByChannel($channel) : null;
        if (null === $configuration || !$configuration->hasModule(TrackingModule::CODE) || null !== $configuration->getTrackerClientKey()) {
            return;
        }

        $credentials = $this->configurationProvider->getCredentials($configuration);
        $key = null === $credentials ? null : $this->accountApi->getAccount($credentials)->trackerClientKey;
        if (null !== $key) {
            $configuration->setTrackerClientKey($key);
            $this->configurationManager->flush();
        }
    }
}
