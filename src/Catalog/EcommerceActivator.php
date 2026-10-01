<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Client\Api\EcommerceApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\AuthenticationException;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Ecommerce\EcommerceAccountsInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class EcommerceActivator implements EcommerceActivatorInterface
{
    public function __construct(
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly EcommerceApiInterface $ecommerceApi,
        private readonly EcommerceAccountsInterface $accounts,
    ) {
    }

    public function activate(ChannelInterface $channel): ?string
    {
        $settings = $this->configurationProvider->getSettings($channel);
        $account = array_values(array_filter(
            $this->accounts->channelsByAccount(),
            static fn (array $channels): bool => in_array($channel->getCode(), array_map(static fn (ChannelInterface $accountChannel): ?string => $accountChannel->getCode(), $channels), true),
        ))[0] ?? null;
        if (null === $settings || null === $account) {
            return null;
        }

        // Activating twice fails, and the ecommerce endpoints answer 403 until it's done.
        try {
            $current = $this->ecommerceApi->getDisplayCurrency($settings->credentials);
        } catch (AuthenticationException) {
            $this->ecommerceApi->activate($settings->credentials);
            $current = null;
        }

        $currency = $account[0]->getBaseCurrency()?->getCode();
        if (null !== $currency && $currency !== $current) {
            $this->ecommerceApi->setDisplayCurrency($settings->credentials, $currency);
        }

        return $currency;
    }
}
