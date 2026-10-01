<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Order;

use Odiseo\SyliusBrevoPlugin\Order\OrderStatusMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\OrderShippingStates;

final class OrderStatusMapperTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, string}> */
    public static function states(): iterable
    {
        yield 'placed' => [OrderInterface::STATE_NEW, OrderPaymentStates::STATE_AWAITING_PAYMENT, OrderShippingStates::STATE_READY, 'pending'];
        yield 'paid' => [OrderInterface::STATE_NEW, OrderPaymentStates::STATE_PAID, OrderShippingStates::STATE_READY, 'paid'];
        yield 'shipped before paid' => [OrderInterface::STATE_NEW, OrderPaymentStates::STATE_AWAITING_PAYMENT, OrderShippingStates::STATE_SHIPPED, 'shipped'];
        yield 'fulfilled' => [OrderInterface::STATE_FULFILLED, OrderPaymentStates::STATE_PAID, OrderShippingStates::STATE_SHIPPED, 'fulfilled'];
        yield 'refunded' => [OrderInterface::STATE_FULFILLED, OrderPaymentStates::STATE_REFUNDED, OrderShippingStates::STATE_SHIPPED, 'refunded'];
        yield 'cancelled' => [OrderInterface::STATE_CANCELLED, OrderPaymentStates::STATE_CANCELLED, OrderShippingStates::STATE_CANCELLED, 'cancelled'];
    }

    #[DataProvider('states')]
    public function testItMapsTheOrderStates(string $state, string $paymentState, string $shippingState, string $status): void
    {
        self::assertSame($status, (new OrderStatusMapper())->map($this->order($state, $paymentState, $shippingState)));
    }

    public function testStatusesCanBeRenamed(): void
    {
        $mapper = new OrderStatusMapper(['fulfilled' => 'completed']);

        self::assertSame('completed', $mapper->map($this->order(OrderInterface::STATE_FULFILLED, OrderPaymentStates::STATE_PAID, OrderShippingStates::STATE_SHIPPED)));
        self::assertSame('paid', $mapper->map($this->order(OrderInterface::STATE_NEW, OrderPaymentStates::STATE_PAID, OrderShippingStates::STATE_READY)));
    }

    private function order(string $state, string $paymentState, string $shippingState): Order
    {
        $order = new Order();
        $order->setState($state);
        $order->setPaymentState($paymentState);
        $order->setShippingState($shippingState);

        return $order;
    }
}
