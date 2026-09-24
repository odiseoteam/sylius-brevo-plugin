<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class ContactAddressResolver implements ContactAddressResolverInterface
{
    /** @param class-string<OrderInterface> $orderClass */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $orderClass,
    ) {
    }

    public function resolve(CustomerInterface $customer): ?AddressInterface
    {
        $address = $customer->getDefaultAddress();
        if (null !== $address || null === $customer->getId()) {
            return $address;
        }

        /** @var OrderInterface|null $order */
        $order = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from($this->orderClass, 'o')
            ->innerJoin('o.billingAddress', 'billingAddress')
            ->andWhere('o.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('o.updatedAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $order?->getBillingAddress();
    }
}
