<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventSenderInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Message\TrackOrderEvent;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

/** Cart events only while it's still a cart, the others only once placed, and only once it has an email. */
final class TrackOrderEventHandler
{
    /** @param OrderRepositoryInterface<OrderInterface> $orderRepository */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TrackingEventSenderInterface $eventSender,
    ) {
    }

    public function __invoke(TrackOrderEvent $message): void
    {
        $order = $this->orderRepository->find($message->orderId);
        $channel = $order?->getChannel();
        $customer = $order?->getCustomer();
        if (!$order instanceof OrderInterface || !$channel instanceof ChannelInterface || !$customer instanceof CustomerInterface) {
            return;
        }

        if (TrackOrderEvent::isCartEvent($message->eventCode) !== (OrderInterface::STATE_CART === $order->getState())) {
            return;
        }

        $this->eventSender->send($message->eventCode, $order, $channel, $customer);
    }
}
