<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Twig\Extension;

use Odiseo\SyliusBrevoPlugin\Ecommerce\MissingExchangeRatesInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class EcommerceExtension extends AbstractExtension
{
    public function __construct(private readonly MissingExchangeRatesInterface $missingExchangeRates)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('odiseo_brevo_missing_exchange_rates', $this->missingExchangeRates(...)),
        ];
    }

    /** @return list<array{channel: string, from: string, to: string}> */
    public function missingExchangeRates(ChannelConfigurationInterface $configuration): array
    {
        $channel = $configuration->getChannel();

        return $channel instanceof ChannelInterface ? $this->missingExchangeRates->of($channel) : [];
    }
}
