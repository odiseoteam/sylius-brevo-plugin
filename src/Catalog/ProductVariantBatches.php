<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class ProductVariantBatches implements ProductVariantBatchesInterface
{
    /** @param class-string<ProductVariantInterface> $variantClass */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $variantClass,
    ) {
    }

    public function batches(array $channelCodes, int $afterId, int $size): iterable
    {
        $lastId = $afterId;
        do {
            /** @var list<ProductVariantInterface> $variants */
            $variants = $this->query($channelCodes, $lastId)
                ->select('variant')
                ->orderBy('variant.id', 'ASC')
                ->setMaxResults($size)
                ->getQuery()
                ->getResult()
            ;

            if ([] === $variants) {
                return;
            }

            yield $variants;

            $id = end($variants)->getId();
            $lastId = is_numeric($id) ? (int) $id : \PHP_INT_MAX;
            $this->entityManager->clear();
        } while (count($variants) === $size);
    }

    public function count(array $channelCodes, int $afterId): int
    {
        return (int) $this->query($channelCodes, $afterId)->select('COUNT(variant.id)')->getQuery()->getSingleScalarResult();
    }

    /** @param list<string> $channelCodes */
    private function query(array $channelCodes, int $afterId): QueryBuilder
    {
        $channels = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from($this->variantClass, 'other')
            ->innerJoin('other.product', 'otherProduct')
            ->innerJoin('otherProduct.channels', 'channel')
            ->andWhere('other.id = variant.id')
            ->andWhere('channel.code IN (:channelCodes)')
        ;

        return $this->entityManager->createQueryBuilder()
            ->from($this->variantClass, 'variant')
            ->andWhere('variant.id > :afterId')
            ->andWhere(sprintf('EXISTS (%s)', $channels->getDQL()))
            ->setParameter('afterId', $afterId)
            ->setParameter('channelCodes', $channelCodes)
        ;
    }
}
