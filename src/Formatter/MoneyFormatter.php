<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Formatter;

use Symfony\Component\Intl\Currencies;

final class MoneyFormatter implements MoneyFormatterInterface
{
    // Sylius stores every amount with a fixed divisor, whatever the currency.
    private const SYLIUS_DIVISOR = 100;

    public function format(int $amount, string $currencyCode): float
    {
        return round($amount / self::SYLIUS_DIVISOR, Currencies::getFractionDigits(strtoupper($currencyCode)));
    }
}
