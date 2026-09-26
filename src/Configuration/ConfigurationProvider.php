<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Configuration;

use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

final class ConfigurationProvider implements ConfigurationProviderInterface
{
    public function __construct(
        private readonly ChannelConfigurationRepositoryInterface $configurationRepository,
        #[\SensitiveParameter]
        private readonly ?string $defaultApiKey = null,
    ) {
    }

    public function getSettings(ChannelInterface $channel): ?BrevoSettings
    {
        $configuration = $this->configurationRepository->findOneByChannel($channel);
        if (null === $configuration || !$configuration->isEnabled()) {
            return null;
        }

        $credentials = $this->getCredentials($configuration);
        if (null === $credentials) {
            return null;
        }

        return new BrevoSettings(
            (string) $channel->getCode(),
            $credentials,
            $configuration->getSenderName(),
            $configuration->getSenderEmail(),
            $configuration->getModules(),
            $configuration->isSyncingGuestContacts(),
            $configuration->isDeletingContactsOfRemovedCustomers(),
            $configuration->getCustomersListId(),
            $configuration->getNewsletterListId(),
            $configuration->getDoubleOptInTemplateId(),
        );
    }

    public function getCredentials(ChannelConfigurationInterface $configuration): ?Credentials
    {
        // A channel without its own key uses the default one, if any.
        $apiKey = $configuration->hasApiKey() ? $configuration->getApiKey() : $this->defaultApiKey;
        if (null === $apiKey || '' === $apiKey) {
            return null;
        }

        return new Credentials($apiKey);
    }
}
