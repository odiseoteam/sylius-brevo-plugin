<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition\CartDeleted;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition\CartUpdated;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition\OrderCompleted;
use Odiseo\SyliusBrevoPlugin\Tracking\Message\TrackOrderEvent;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackingModule;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Symfony\Contracts\Service\ResetInterface;

/** Cart changes (items, totals, the customer) and completed checkouts, sent once the flush is done. */
final class OrderEventsListener implements ResetInterface
{
    private const CART_FIELDS = ['itemsTotal', 'total', 'customer'];

    /** @var array<int, OrderInterface> */
    private array $changed = [];

    /** @var array<int, true> orders whose checkout was completed */
    private array $completed = [];

    /** @var array<int, true> carts that had items before the flush */
    private array $hadItems = [];

    public function __construct(
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly BrevoMessageDispatcherInterface $dispatcher,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ([...$unitOfWork->getScheduledEntityInsertions(), ...$unitOfWork->getScheduledEntityUpdates()] as $entity) {
            if (!$entity instanceof OrderInterface) {
                continue;
            }

            $changeSet = $unitOfWork->getEntityChangeSet($entity);
            if (OrderCheckoutStates::STATE_COMPLETED === ($changeSet['checkoutState'][1] ?? null)) {
                $this->completed[spl_object_id($entity)] = true;
            }
            if (0 < ($changeSet['itemsTotal'][0] ?? 0)) {
                $this->hadItems[spl_object_id($entity)] = true;
            }
            if (isset($this->completed[spl_object_id($entity)]) || [] !== array_intersect(self::CART_FIELDS, array_keys($changeSet))) {
                $this->changed[spl_object_id($entity)] = $entity;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $changed = $this->changed;
        $completed = $this->completed;
        $hadItems = $this->hadItems;
        $this->reset();

        foreach ($changed as $objectId => $order) {
            $orderId = $order->getId();
            $channel = $order->getChannel();
            if (!is_int($orderId) || !$channel instanceof ChannelInterface || true !== $this->configurationProvider->getSettings($channel)?->hasModule(TrackingModule::CODE)) {
                continue;
            }

            $eventCode = match (true) {
                isset($completed[$objectId]) => OrderCompleted::CODE,
                OrderInterface::STATE_CART !== $order->getState() => null,
                !$order->getItems()->isEmpty() => CartUpdated::CODE,
                isset($hadItems[$objectId]) => CartDeleted::CODE,
                default => null,
            };
            if (null !== $eventCode) {
                $this->dispatcher->dispatch(new TrackOrderEvent((string) $channel->getCode(), $orderId, $eventCode));
            }
        }
    }

    public function reset(): void
    {
        $this->changed = [];
        $this->completed = [];
        $this->hadItems = [];
    }
}
