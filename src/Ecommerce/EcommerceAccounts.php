<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Ecommerce;

use Odiseo\SyliusBrevoPlugin\Catalog\CatalogModule;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Order\OrdersModule;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Contracts\Service\ResetInterface;

final class EcommerceAccounts implements EcommerceAccountsInterface, ResetInterface
{
    /** @var array<string, string|null>|null channel code => account currency */
    private ?array $currencies = null;

    public function __construct(
        private readonly ChannelConfigurationRepositoryInterface $configurationRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
    ) {
    }

    public function channelsByAccount(): array
    {
        $groups = [];
        /** @var ChannelConfigurationInterface $configuration */
        foreach ($this->configurationRepository->findBy(['enabled' => true], ['id' => 'ASC']) as $configuration) {
            $channel = $configuration->getChannel();
            if (!$channel instanceof ChannelInterface) {
                continue;
            }

            $settings = $this->configurationProvider->getSettings($channel);
            if (null !== $settings && ($settings->hasModule(CatalogModule::CODE) || $settings->hasModule(OrdersModule::CODE))) {
                $groups[$settings->credentials->apiKey][] = $channel;
            }
        }

        return array_values($groups);
    }

    public function currencyOf(ChannelInterface $channel): ?string
    {
        // Kept by code: long CLI runs clear the entity manager.
        if (null === $this->currencies) {
            $this->currencies = [];
            foreach ($this->channelsByAccount() as $channels) {
                $currency = $channels[0]->getBaseCurrency()?->getCode();
                foreach ($channels as $accountChannel) {
                    $this->currencies[(string) $accountChannel->getCode()] = $currency;
                }
            }
        }

        return $this->currencies[(string) $channel->getCode()] ?? null;
    }

    public function reset(): void
    {
        $this->currencies = null;
    }
}
