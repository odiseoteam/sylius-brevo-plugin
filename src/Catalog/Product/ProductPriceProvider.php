<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Product;

use Odiseo\SyliusBrevoPlugin\Ecommerce\AccountMoneyFormatterInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/** Channel price, the original one when it's higher (a discount), in the account's currency; and the available stock. */
final class ProductPriceProvider implements ProductPayloadProviderInterface
{
    public function __construct(private readonly AccountMoneyFormatterInterface $moneyFormatter)
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
            'price' => $this->moneyFormatter->format($price, $currency, $channel),
            'alternativePrice' => null !== $originalPrice && $originalPrice > $price ? $this->moneyFormatter->format($originalPrice, $currency, $channel) : null,
            'stock' => $variant->isTracked() ? max(0, (int) $variant->getOnHand() - (int) $variant->getOnHold()) : null,
        ];
    }
}
