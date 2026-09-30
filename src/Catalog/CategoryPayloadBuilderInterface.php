<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Client\Model\CategoryData;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;

/** Decorate `odiseo_brevo.catalog.category_payload_builder` to change what is sent. */
interface CategoryPayloadBuilderInterface
{
    /** Id = taxon code; a disabled taxon, or one outside the channel, is sent as deleted. */
    public function build(TaxonInterface $taxon, ChannelInterface $channel): CategoryData;
}
