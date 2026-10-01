<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Order;

use Odiseo\SyliusBrevoPlugin\Client\Model\OrderData;
use Sylius\Component\Core\Model\OrderInterface;

interface OrderPayloadBuilderInterface
{
    /** Null for an order without a completed checkout, customer or channel. */
    public function build(OrderInterface $order): ?OrderData;
}
