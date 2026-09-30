<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Product;

use Odiseo\SyliusBrevoPlugin\Catalog\ChannelTaxonsInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;

/** Codes of the product's taxons in the channel, with their parents so a parent category lists them too. */
final class ProductCategoriesProvider implements ProductPayloadProviderInterface
{
    public function __construct(private readonly ChannelTaxonsInterface $channelTaxons)
    {
    }

    public function provide(ProductVariantInterface $variant, ChannelInterface $channel): array
    {
        $product = $variant->getProduct();
        if (!$product instanceof ProductInterface) {
            return [];
        }

        $categories = [];
        foreach ([$product->getMainTaxon(), ...$product->getTaxons()] as $taxon) {
            for (; $taxon instanceof TaxonInterface && $this->channelTaxons->contains($channel, $taxon); $taxon = $taxon->getParent()) {
                $categories[(string) $taxon->getCode()] = true;
            }
        }

        return ['categories' => array_keys($categories)];
    }
}
