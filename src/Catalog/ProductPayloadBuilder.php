<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Catalog\Product\ProductPayloadProviderInterface;
use Odiseo\SyliusBrevoPlugin\Client\Model\ProductData;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class ProductPayloadBuilder implements ProductPayloadBuilderInterface
{
    /** @param iterable<ProductPayloadProviderInterface> $providers later ones win on the same field */
    public function __construct(private readonly iterable $providers)
    {
    }

    public function build(ProductVariantInterface $variant, ChannelInterface $channel): ProductData
    {
        $code = (string) $variant->getCode();
        $product = $variant->getProduct();
        $name = $product?->getTranslation($channel->getDefaultLocale()?->getCode())->getName() ?? $code;

        if (!$product instanceof ProductInterface || !$this->isSold($variant, $product, $channel)) {
            return new ProductData($code, $name, deleted: true);
        }

        // Brevo groups variants by parentId; a single-variant product often shares its code.
        $fields = ['parentId' => $product->getCode() === $code ? null : $product->getCode()];
        $metaInfo = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->provide($variant, $channel) as $field => $value) {
                if ('metaInfo' === $field && is_array($value)) {
                    $metaInfo = [...$metaInfo, ...$value];
                } else {
                    $fields[$field] = $value;
                }
            }
        }
        // Left out when empty: it would be encoded as a JSON list, not an object.
        $fields['metaInfo'] = array_filter($metaInfo, self::isKnown(...)) ?: null;

        return new ProductData($code, $name, array_filter($fields, self::isKnown(...)));
    }

    private static function isKnown(mixed $value): bool
    {
        return null !== $value && '' !== $value;
    }

    private function isSold(ProductVariantInterface $variant, ProductInterface $product, ChannelInterface $channel): bool
    {
        return $product->isEnabled() &&
            $variant->isEnabled() &&
            $product->hasChannel($channel) &&
            null !== $variant->getChannelPricingForChannel($channel)?->getPrice()
        ;
    }
}
