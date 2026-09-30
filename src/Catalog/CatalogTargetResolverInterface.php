<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;

/** Channels with the catalog module, one per Brevo account (channels sharing an API key share a catalog). */
interface CatalogTargetResolverInterface
{
    /** @return list<ChannelInterface> the first channel of each account */
    public function accounts(): array;

    /** @return list<non-empty-list<ChannelInterface>> the channels of each account */
    public function channelsByAccount(): array;

    /** @return list<ChannelInterface> per account, the first channel that shows the taxon, else the first one */
    public function resolve(TaxonInterface $taxon): array;
}
