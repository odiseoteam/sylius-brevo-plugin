<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Product;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * Adds Brevo product fields (price, url, brand, metaInfo...) for a variant sold in the channel.
 * Tag: `odiseo_brevo.product_payload_provider`.
 */
interface ProductPayloadProviderInterface
{
    /**
     * Null or "" means unknown: it's not sent. Later providers win on the same field, except
     * `metaInfo`, merged by key.
     *
     * @return array<string, mixed>
     */
    public function provide(ProductVariantInterface $variant, ChannelInterface $channel): array;
}
