<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

final class SyncCategories extends AbstractBrevoMessage
{
    /**
     * @param list<string> $taxonCodes taxons to create or update
     * @param array<string, string> $deletedTaxons code => name of taxons gone from Sylius
     */
    public function __construct(
        string $channelCode,
        public readonly array $taxonCodes,
        public readonly array $deletedTaxons = [],
    ) {
        $codes = [...$taxonCodes, ...array_keys($deletedTaxons)];
        sort($codes);

        parent::__construct($channelCode, 'categories:' . hash('xxh128', implode(',', $codes)));
    }
}
