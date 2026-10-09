<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Client\Api\EventsApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Model\EventData;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactExtId;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition\OrderCompleted;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventResolverInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Message\TrackOrderEvent;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

/** Cart events only while it's still a cart, and only once it has an email. */
final class TrackOrderEventHandler
{
    /** @param OrderRepositoryInterface<OrderInterface> $orderRepository */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly TrackingEventResolverInterface $eventResolver,
        private readonly EventsApiInterface $eventsApi,
    ) {
    }

    public function __invoke(TrackOrderEvent $message): void
    {
        $order = $this->orderRepository->find($message->orderId);
        $channel = $order?->getChannel();
        $customer = $order?->getCustomer();
        $email = $customer?->getEmail();
        if (!$order instanceof OrderInterface || !$channel instanceof ChannelInterface || !$customer instanceof CustomerInterface || null === $email) {
            return;
        }

        $isCart = OrderInterface::STATE_CART === $order->getState();
        if ((OrderCompleted::CODE === $message->eventCode) === $isCart) {
            return;
        }

        $settings = $this->configurationProvider->getSettings($channel);
        $event = $this->eventResolver->resolve($message->eventCode, $order, $channel);
        if (null === $settings || null === $event) {
            return;
        }

        $this->eventsApi->track($settings->credentials, new EventData(
            $event->name,
            array_filter(['email_id' => $email, 'ext_id' => ContactExtId::of($customer)], static fn (string $value): bool => '' !== $value),
            $event->properties,
        ));
    }
}
