<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Ecommerce;

use Sylius\Component\Core\Model\ChannelInterface;

/** Channels with an ecommerce module (catalog or orders), grouped by Brevo account. */
interface EcommerceAccountsInterface
{
    /** @return list<non-empty-list<ChannelInterface>> in configuration order */
    public function channelsByAccount(): array;

    /**
     * Brevo shows one currency per account: the base currency of its first configured channel.
     * Null when the channel has no ecommerce module.
     */
    public function currencyOf(ChannelInterface $channel): ?string;
}
