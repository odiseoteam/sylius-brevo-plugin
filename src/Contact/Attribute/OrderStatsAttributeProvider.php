<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Attribute;

use Odiseo\SyliusBrevoPlugin\Client\Model\Attribute;
use Odiseo\SyliusBrevoPlugin\Contact\CustomerOrderStatsProviderInterface;
use Odiseo\SyliusBrevoPlugin\Formatter\DateFormatterInterface;
use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatterInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/** Purchase history in the channel (RFM): how many, how much, when. */
final class OrderStatsAttributeProvider implements ContactAttributeProviderInterface
{
    public function __construct(
        private readonly CustomerOrderStatsProviderInterface $statsProvider,
        private readonly MoneyFormatterInterface $moneyFormatter,
        private readonly DateFormatterInterface $dateFormatter,
    ) {
    }

    public function getAttributeTypes(): array
    {
        return [
            'orders_count' => Attribute::TYPE_FLOAT,
            'total_spent' => Attribute::TYPE_FLOAT,
            'average_order_value' => Attribute::TYPE_FLOAT,
            'first_order_date' => Attribute::TYPE_DATE,
            'last_order_date' => Attribute::TYPE_DATE,
            'locale' => Attribute::TYPE_TEXT,
        ];
    }

    public function provide(CustomerInterface $customer, ChannelInterface $channel): array
    {
        $stats = $this->statsProvider->getStats($customer, $channel);
        $currencyCode = (string) $channel->getBaseCurrency()?->getCode();

        return [
            'orders_count' => $stats->count,
            'total_spent' => $this->moneyFormatter->format($stats->total, $currencyCode),
            'average_order_value' => $this->moneyFormatter->format($stats->averageTotal(), $currencyCode),
            'first_order_date' => null === $stats->firstOrderAt ? null : $this->dateFormatter->formatDate($stats->firstOrderAt),
            'last_order_date' => null === $stats->lastOrderAt ? null : $this->dateFormatter->formatDate($stats->lastOrderAt),
            'locale' => $stats->lastLocaleCode ?? $channel->getDefaultLocale()?->getCode(),
        ];
    }
}
