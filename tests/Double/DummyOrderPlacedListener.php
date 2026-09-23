<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Double;

use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/** Stands in for a real module until one exists: sends each placed order to Brevo. */
final class DummyOrderPlacedListener
{
    public function __construct(
        private readonly BrevoMessageDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(GenericEvent $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof OrderInterface || null === $order->getChannel()) {
            return;
        }

        $this->dispatcher->dispatch(new DummyOrderPlaced((string) $order->getChannel()->getCode(), (string) $order->getNumber()));
    }
}
