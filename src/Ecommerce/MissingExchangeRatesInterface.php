<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Ecommerce;

use Sylius\Component\Core\Model\ChannelInterface;

interface MissingExchangeRatesInterface
{
    /**
     * Channels of the channel's Brevo account in another currency than the account's, without an
     * exchange rate: their amounts reach Brevo unconverted.
     *
     * @return list<array{channel: string, from: string, to: string}>
     */
    public function of(ChannelInterface $channel): array;
}
