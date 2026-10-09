<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

use Odiseo\SyliusBrevoPlugin\Catalog\Product\ProductImagePath;
use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatterInterface;
use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductInterface;

/** Properties of the cart and order events, in the order's currency. */
final class OrderEventProperties
{
    public function __construct(
        private readonly MoneyFormatterInterface $moneyFormatter,
        private readonly ChannelUrlGeneratorInterface $urlGenerator,
    ) {
    }

    /** @return array<string, mixed> */
    public function forCart(OrderInterface $order, ChannelInterface $channel): array
    {
        $localeCode = $order->getLocaleCode();

        return [
            // Lets Brevo tell the updates of one cart apart from another one's.
            'cart_id' => $order->getTokenValue(),
            'total' => $this->money($order->getTotal(), $order),
            'currency' => $order->getCurrencyCode(),
            'url' => null === $localeCode ? null : $this->urlGenerator->generate($channel, 'sylius_shop_cart_summary', ['_locale' => $localeCode]),
            'items' => $this->items($order, $channel),
        ];
    }

    /** @return array<string, mixed> */
    public function forOrder(OrderInterface $order, ChannelInterface $channel): array
    {
        return [
            'order_id' => $order->getNumber(),
            'total' => $this->money($order->getTotal(), $order),
            'items_total' => $this->money($order->getItemsTotal(), $order),
            'shipping_total' => $this->money($order->getShippingTotal(), $order),
            'tax_total' => $this->money($order->getTaxTotal(), $order),
            'discount_total' => $this->money(-$order->getOrderPromotionTotal(), $order),
            'currency' => $order->getCurrencyCode(),
            'coupon' => $order->getPromotionCoupon()?->getCode(),
            'items' => $this->items($order, $channel),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function items(OrderInterface $order, ChannelInterface $channel): array
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            if (0 === $item->getQuantity()) {
                continue;
            }

            $product = $item->getProduct();
            $image = $product instanceof ProductInterface ? ProductImagePath::of($item->getVariant(), $product) : null;

            $items[] = array_filter([
                'product_id' => $item->getVariant()?->getCode(),
                'name' => $item->getProductName(),
                'variant_name' => $item->getVariantName(),
                'quantity' => $item->getQuantity(),
                // Unit price after discounts.
                'price' => round($this->money($item->getTotal(), $order) / $item->getQuantity(), 2),
                'url' => $this->productUrl($product, $order, $channel),
                'image' => null === $image ? null : $this->urlGenerator->generateImageUrl($channel, $image),
            ], static fn (mixed $value): bool => null !== $value && '' !== $value);
        }

        return $items;
    }

    private function productUrl(?ProductInterface $product, OrderInterface $order, ChannelInterface $channel): ?string
    {
        $localeCode = $order->getLocaleCode();
        $slug = $product?->getTranslation($localeCode)->getSlug();

        return null === $slug || null === $localeCode ? null : $this->urlGenerator->generate($channel, 'sylius_shop_product_show', [
            'slug' => $slug,
            '_locale' => $localeCode,
        ]);
    }

    private function money(int $amount, OrderInterface $order): float
    {
        return $this->moneyFormatter->format($amount, (string) $order->getCurrencyCode());
    }
}
