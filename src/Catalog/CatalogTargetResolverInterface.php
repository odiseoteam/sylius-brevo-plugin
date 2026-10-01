<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;

/** Channels with the catalog module, one per Brevo account (channels sharing an API key share a catalog). */
interface CatalogTargetResolverInterface
{
    /** @return list<non-empty-list<ChannelInterface>> the channels of each account */
    public function channelsByAccount(): array;

    /** @return list<ChannelInterface> per account, the first channel that shows the taxon, else the first one */
    public function resolveTaxon(TaxonInterface $taxon): array;

    /** @return list<ChannelInterface> per account, the channel picked by productChannel() */
    public function resolveProduct(ProductInterface $product): array;

    /**
     * The channel to read the product from: one selling it in the account's currency, else one
     * selling it, else the first one.
     *
     * @param non-empty-list<ChannelInterface> $channels the account's channels
     */
    public function productChannel(ProductInterface $product, array $channels): ChannelInterface;
}
