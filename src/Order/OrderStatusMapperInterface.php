<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Order;

use Sylius\Component\Core\Model\OrderInterface;

/** The Brevo status of an order, from its order, payment and shipping states. */
interface OrderStatusMapperInterface
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const SHIPPED = 'shipped';

    public const FULFILLED = 'fulfilled';

    public const CANCELLED = 'cancelled';

    public const REFUNDED = 'refunded';

    public function map(OrderInterface $order): string;
}
