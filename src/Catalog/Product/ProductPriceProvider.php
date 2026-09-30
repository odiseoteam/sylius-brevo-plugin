<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Product;

use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatterInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/** Channel price, the original one when it's higher (a discount), and the available stock. */
final class ProductPriceProvider implements ProductPayloadProviderInterface
{
    public function __construct(private readonly MoneyFormatterInterface $moneyFormatter)
    {
    }

    public function provide(ProductVariantInterface $variant, ChannelInterface $channel): array
    {
        $pricing = $variant->getChannelPricingForChannel($channel);
        $currency = $channel->getBaseCurrency()?->getCode();
        $price = $pricing?->getPrice();
        if (null === $currency || null === $price) {
            return [];
        }

        $originalPrice = $pricing->getOriginalPrice();

        return [
            'price' => $this->moneyFormatter->format($price, $currency),
            'alternativePrice' => null !== $originalPrice && $originalPrice > $price ? $this->moneyFormatter->format($originalPrice, $currency) : null,
            'stock' => $variant->isTracked() ? max(0, (int) $variant->getOnHand() - (int) $variant->getOnHold()) : null,
        ];
    }
}
