<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Odiseo\SyliusBrevoPlugin\Contact\ContactExtId;
use Odiseo\SyliusBrevoPlugin\Contact\ContactTargetResolverInterface;
use Odiseo\SyliusBrevoPlugin\Contact\Message\DeleteContact;
use Odiseo\SyliusBrevoPlugin\Contact\Message\SyncContact;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Collects customers touched by a flush and sends them once it's done, when they have ids and the
 * data is committed. Touched means: the customer itself, its default address, or an order of its
 * that got a billing address or changed state (checkout, payment, cancellation).
 */
final class CustomerChangesListener implements ResetInterface
{
    /** @var array<int, CustomerInterface> */
    private array $changed = [];

    /** @var list<DeleteContact> */
    private array $deletions = [];

    private const ORDER_FIELDS = ['customer', 'billingAddress', 'state', 'checkoutState', 'paymentState'];

    public function __construct(
        private readonly ContactTargetResolverInterface $targetResolver,
        private readonly BrevoMessageDispatcherInterface $dispatcher,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ([...$unitOfWork->getScheduledEntityInsertions(), ...$unitOfWork->getScheduledEntityUpdates()] as $entity) {
            $customer = match (true) {
                $entity instanceof CustomerInterface => $entity,
                $entity instanceof OrderInterface => $this->orderChanged($entity, $unitOfWork) ? $entity->getCustomer() : null,
                $entity instanceof AddressInterface => $this->addressOwner($entity, $unitOfWork),
                default => null,
            };

            if ($customer instanceof CustomerInterface) {
                $this->changed[spl_object_id($customer)] = $customer;
            }
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if (!$entity instanceof CustomerInterface || null === $entity->getId()) {
                continue;
            }

            unset($this->changed[spl_object_id($entity)]);
            foreach ($this->targetResolver->accounts() as $channel) {
                $this->deletions[] = new DeleteContact((string) $channel->getCode(), ContactExtId::of($entity), $entity->getEmail());
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $changed = $this->changed;
        $deletions = $this->deletions;
        $this->reset();

        foreach ($changed as $customer) {
            $customerId = $customer->getId();
            if (!is_int($customerId) || null === $customer->getEmail()) {
                continue;
            }

            foreach ($this->targetResolver->resolve($customer) as $channel) {
                $this->dispatcher->dispatch(new SyncContact((string) $channel->getCode(), $customerId));
            }
        }

        foreach ($deletions as $deletion) {
            $this->dispatcher->dispatch($deletion);
        }
    }

    public function reset(): void
    {
        $this->changed = [];
        $this->deletions = [];
    }

    private function orderChanged(OrderInterface $order, UnitOfWork $unitOfWork): bool
    {
        if (null === $order->getId()) {
            return null !== $order->getBillingAddress();
        }

        return [] !== array_intersect(self::ORDER_FIELDS, array_keys($unitOfWork->getEntityChangeSet($order)));
    }

    /** The customer of a default address, or of the order that bills to this address. */
    private function addressOwner(AddressInterface $address, UnitOfWork $unitOfWork): ?CustomerInterface
    {
        $customer = $address->getCustomer();
        if ($customer instanceof CustomerInterface && $customer->getDefaultAddress() === $address) {
            return $customer;
        }

        foreach ($unitOfWork->getIdentityMap() as $class => $entities) {
            if (!is_a($class, OrderInterface::class, true)) {
                continue;
            }

            foreach ($entities as $order) {
                if ($order instanceof OrderInterface && $order->getBillingAddress() === $address) {
                    $owner = $order->getCustomer();

                    return $owner instanceof CustomerInterface ? $owner : null;
                }
            }
        }

        return null;
    }
}
