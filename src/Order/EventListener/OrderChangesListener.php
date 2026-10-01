<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Order\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Odiseo\SyliusBrevoPlugin\Order\Message\SyncOrder;
use Odiseo\SyliusBrevoPlugin\Order\OrdersModule;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Contracts\Service\ResetInterface;

/** Sends completed orders whose checkout, order, payment or shipping state changed, once the flush is done. */
final class OrderChangesListener implements ResetInterface
{
    private const FIELDS = ['checkoutState', 'state', 'paymentState', 'shippingState', 'total'];

    /** @var array<int, OrderInterface> */
    private array $changed = [];

    public function __construct(
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly BrevoMessageDispatcherInterface $dispatcher,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ([...$unitOfWork->getScheduledEntityInsertions(), ...$unitOfWork->getScheduledEntityUpdates()] as $entity) {
            if ($entity instanceof OrderInterface && [] !== array_intersect(self::FIELDS, array_keys($unitOfWork->getEntityChangeSet($entity)))) {
                $this->changed[spl_object_id($entity)] = $entity;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $changed = $this->changed;
        $this->reset();

        foreach ($changed as $order) {
            $orderId = $order->getId();
            $channel = $order->getChannel();
            if (!is_int($orderId) || null === $order->getCheckoutCompletedAt() || !$channel instanceof ChannelInterface) {
                continue;
            }

            if ($this->configurationProvider->getSettings($channel)?->hasModule(OrdersModule::CODE)) {
                $this->dispatcher->dispatch(new SyncOrder((string) $channel->getCode(), $orderId));
            }
        }
    }

    public function reset(): void
    {
        $this->changed = [];
    }
}
