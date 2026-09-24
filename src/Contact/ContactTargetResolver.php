<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class ContactTargetResolver implements ContactTargetResolverInterface
{
    public function __construct(
        private readonly ChannelConfigurationRepositoryInterface $configurationRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly CustomerOrderStatsProviderInterface $statsProvider,
    ) {
    }

    public function resolve(CustomerInterface $customer): array
    {
        $isGuest = null === $customer->getUser();
        $lastOrderChannel = $this->statsProvider->getLastOrderChannel($customer);

        $targets = [];
        foreach ($this->groupByAccount() as $channels) {
            $channels = array_values(array_filter(
                $channels,
                static fn (array $target): bool => !$isGuest || $target['settings']->syncingGuestContacts,
            ));
            if ([] === $channels) {
                continue;
            }

            $preferred = array_filter($channels, static fn (array $target): bool => $target['channel'] === $lastOrderChannel);
            $targets[] = ([] === $preferred ? $channels[0] : reset($preferred))['channel'];
        }

        return $targets;
    }

    public function accounts(): array
    {
        return array_values(array_map(static fn (array $channels): ChannelInterface => $channels[0]['channel'], $this->groupByAccount()));
    }

    /** @return array<string, non-empty-list<array{channel: ChannelInterface, settings: BrevoSettings}>> */
    private function groupByAccount(): array
    {
        $groups = [];
        /** @var ChannelConfigurationInterface $configuration */
        foreach ($this->configurationRepository->findBy(['enabled' => true], ['id' => 'ASC']) as $configuration) {
            $channel = $configuration->getChannel();
            if (!$channel instanceof ChannelInterface) {
                continue;
            }

            $settings = $this->configurationProvider->getSettings($channel);
            if (null === $settings || !$settings->hasModule(ContactsModule::CODE)) {
                continue;
            }

            $groups[$settings->credentials->apiKey][] = ['channel' => $channel, 'settings' => $settings];
        }

        return $groups;
    }
}
