<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

final class SyncProducts extends AbstractBrevoMessage
{
    /**
     * @param list<string> $variantCodes variants to create or update
     * @param array<string, string> $deletedVariants code => product name of variants gone from Sylius
     */
    public function __construct(
        string $channelCode,
        public readonly array $variantCodes,
        public readonly array $deletedVariants = [],
    ) {
        $codes = [...$variantCodes, ...array_keys($deletedVariants)];
        sort($codes);

        parent::__construct($channelCode, 'products:' . hash('xxh128', implode(',', $codes)));
    }
}
