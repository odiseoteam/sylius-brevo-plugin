<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Client\Api\EcommerceApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\AuthenticationException;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class EcommerceActivator implements EcommerceActivatorInterface
{
    public function __construct(
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly EcommerceApiInterface $ecommerceApi,
    ) {
    }

    public function activate(ChannelInterface $channel): ?string
    {
        $settings = $this->configurationProvider->getSettings($channel);
        if (null === $settings || !$settings->hasModule(CatalogModule::CODE)) {
            return null;
        }

        // Activating twice fails, and the ecommerce endpoints answer 403 until it's done.
        try {
            $current = $this->ecommerceApi->getDisplayCurrency($settings->credentials);
        } catch (AuthenticationException) {
            $this->ecommerceApi->activate($settings->credentials);
            $current = null;
        }

        $currency = $channel->getBaseCurrency()?->getCode();
        if (null !== $currency && $currency !== $current) {
            $this->ecommerceApi->setDisplayCurrency($settings->credentials, $currency);
        }

        return $currency;
    }
}
