<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Sylius\Component\Core\Model\ChannelInterface;

interface EcommerceActivatorInterface
{
    /**
     * Activates Brevo Ecommerce on the channel's account when it's off, and shows amounts in the
     * account's currency (its first channel's base currency). Safe to repeat.
     *
     * @return string|null the currency set; null when the channel has no catalog or orders module
     *
     * @throws BrevoException
     */
    public function activate(ChannelInterface $channel): ?string;
}
