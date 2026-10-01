<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Order;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\OrderShippingStates;

final class OrderStatusMapper implements OrderStatusMapperInterface
{
    /** @param array<string, string> $statuses self::* => Brevo status */
    public function __construct(private readonly array $statuses = [])
    {
    }

    public function map(OrderInterface $order): string
    {
        $status = match (true) {
            OrderInterface::STATE_CANCELLED === $order->getState() => self::CANCELLED,
            OrderPaymentStates::STATE_REFUNDED === $order->getPaymentState() => self::REFUNDED,
            OrderInterface::STATE_FULFILLED === $order->getState() => self::FULFILLED,
            OrderShippingStates::STATE_SHIPPED === $order->getShippingState() => self::SHIPPED,
            OrderPaymentStates::STATE_PAID === $order->getPaymentState() => self::PAID,
            default => self::PENDING,
        };

        return $this->statuses[$status] ?? $status;
    }
}
