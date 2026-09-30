<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Sylius\Component\Core\Model\ProductVariantInterface;

interface ProductVariantBatchesInterface
{
    /**
     * Variants of the products in any of the channels, by id. The entity manager is cleared between
     * batches, so reload what you keep across them.
     *
     * @param list<string> $channelCodes
     *
     * @return iterable<non-empty-list<ProductVariantInterface>>
     */
    public function batches(array $channelCodes, int $afterId, int $size): iterable;

    /** @param list<string> $channelCodes */
    public function count(array $channelCodes, int $afterId): int;
}
