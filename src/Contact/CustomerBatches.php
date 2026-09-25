<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylius\Component\Core\Model\CustomerInterface;

final class CustomerBatches implements CustomerBatchesInterface
{
    /** @param class-string<CustomerInterface> $customerClass */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $customerClass,
    ) {
    }

    public function batches(CustomerFilter $filter, int $size): iterable
    {
        $lastId = 0;
        do {
            /** @var list<CustomerInterface> $customers */
            $customers = $this->query($filter)
                ->select('c')
                ->andWhere('c.id > :lastId')
                ->setParameter('lastId', $lastId)
                ->orderBy('c.id', 'ASC')
                ->setMaxResults($size)
                ->getQuery()
                ->getResult()
            ;

            if ([] === $customers) {
                return;
            }

            yield $customers;

            $id = end($customers)->getId();
            $lastId = is_numeric($id) ? (int) $id : \PHP_INT_MAX;
            $this->entityManager->clear();
        } while (count($customers) === $size);
    }

    public function count(CustomerFilter $filter): int
    {
        return (int) $this->query($filter)->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
    }

    private function query(CustomerFilter $filter): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from($this->customerClass, 'c')
            ->andWhere('c.email IS NOT NULL')
        ;

        if (!$filter->includeGuests) {
            $queryBuilder->innerJoin('c.user', 'user');
        }

        if (null !== $filter->since) {
            $queryBuilder
                ->andWhere('c.createdAt >= :since OR c.updatedAt >= :since')
                ->setParameter('since', $filter->since)
            ;
        }

        if ($filter->onlySubscribed) {
            $queryBuilder->andWhere('c.subscribedToNewsletter = true');
        }

        return $queryBuilder;
    }
}
