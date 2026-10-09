<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Product;

use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class ProductImagePath
{
    /** The variant's own image, else the product's; a "main" one first. */
    public static function of(?ProductVariantInterface $variant, ProductInterface $product): ?string
    {
        foreach ([$variant?->getImages(), $product->getImages()] as $images) {
            if (null === $images) {
                continue;
            }

            $main = $images->filter(static fn (ImageInterface $image): bool => 'main' === $image->getType())->first();
            $image = false === $main ? $images->first() : $main;
            if ($image instanceof ImageInterface && null !== $image->getPath()) {
                return $image->getPath();
            }
        }

        return null;
    }
}
