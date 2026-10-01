<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Ecommerce;

use Sylius\Component\Core\Model\ChannelInterface;

/** Amounts in the currency of the channel's Brevo account, converted with Sylius' exchange rates. */
interface AccountMoneyFormatterInterface
{
    /** Without an exchange rate the amount is kept in its own currency (and a warning logged). */
    public function format(int $amount, string $currencyCode, ChannelInterface $channel): float;

    /** Whether the amount is shown in another currency than its own. */
    public function converts(string $currencyCode, ChannelInterface $channel): bool;
}
