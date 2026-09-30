<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;

/** The taxons a channel shows: those under its menu taxon, or every non-root one without it. */
interface ChannelTaxonsInterface
{
    public function contains(ChannelInterface $channel, TaxonInterface $taxon): bool;

    /** @return list<TaxonInterface> enabled or not, tree order */
    public function all(ChannelInterface $channel): array;
}
