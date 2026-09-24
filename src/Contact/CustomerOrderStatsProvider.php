<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class CustomerOrderStatsProvider implements CustomerOrderStatsProviderInterface
{
    private const PLACED_STATES = [OrderInterface::STATE_NEW, OrderInterface::STATE_FULFILLED];

    /** @param class-string<OrderInterface> $orderClass */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $orderClass,
    ) {
    }

    public function getStats(CustomerInterface $customer, ChannelInterface $channel): CustomerOrderStats
    {
        if (null === $customer->getId()) {
            return new CustomerOrderStats();
        }

        /** @var array{orders: int|string, total: int|string|null, firstAt: string|null, lastAt: string|null} $row */
        $row = $this->placedOrders($customer)
            ->select('COUNT(o.id) AS orders, SUM(o.total) AS total, MIN(o.checkoutCompletedAt) AS firstAt, MAX(o.checkoutCompletedAt) AS lastAt')
            ->andWhere('o.channel = :channel')
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getSingleResult()
        ;

        if (0 === (int) $row['orders']) {
            return new CustomerOrderStats();
        }

        return new CustomerOrderStats(
            (int) $row['orders'],
            (int) $row['total'],
            null === $row['firstAt'] ? null : new \DateTimeImmutable($row['firstAt']),
            null === $row['lastAt'] ? null : new \DateTimeImmutable($row['lastAt']),
            $this->lastOrder($customer, $channel)?->getLocaleCode(),
        );
    }

    public function getLastOrderChannel(CustomerInterface $customer): ?ChannelInterface
    {
        return null === $customer->getId() ? null : $this->lastOrder($customer)?->getChannel();
    }

    private function lastOrder(CustomerInterface $customer, ?ChannelInterface $channel = null): ?OrderInterface
    {
        $queryBuilder = $this->placedOrders($customer)
            ->orderBy('o.checkoutCompletedAt', 'DESC')
            ->setMaxResults(1)
        ;

        if (null !== $channel) {
            $queryBuilder->andWhere('o.channel = :channel')->setParameter('channel', $channel);
        }

        /** @var OrderInterface|null $order */
        $order = $queryBuilder->getQuery()->getOneOrNullResult();

        return $order;
    }

    private function placedOrders(CustomerInterface $customer): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->from($this->orderClass, 'o')
            ->select('o')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.checkoutCompletedAt IS NOT NULL')
            ->andWhere('o.state IN (:states)')
            ->setParameter('customer', $customer)
            ->setParameter('states', self::PLACED_STATES)
        ;
    }
}
