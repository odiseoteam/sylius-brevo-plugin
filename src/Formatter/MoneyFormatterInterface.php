<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Formatter;

interface MoneyFormatterInterface
{
    /** Converts a Sylius amount (stored ×100) into a decimal rounded to the currency's fraction digits. */
    public function format(int $amount, string $currencyCode): float;
}
