<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

interface CustomerOrderStatsProviderInterface
{
    /** Placed orders (checkout completed, not cancelled) of the customer in the channel. */
    public function getStats(CustomerInterface $customer, ChannelInterface $channel): CustomerOrderStats;

    public function getLastOrderChannel(CustomerInterface $customer): ?ChannelInterface;
}
