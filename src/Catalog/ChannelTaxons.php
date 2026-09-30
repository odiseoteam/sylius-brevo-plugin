<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;

final class ChannelTaxons implements ChannelTaxonsInterface
{
    /** @param class-string<TaxonInterface> $taxonClass */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $taxonClass,
    ) {
    }

    public function contains(ChannelInterface $channel, TaxonInterface $taxon): bool
    {
        $menuTaxon = $channel->getMenuTaxon();
        if (null === $menuTaxon) {
            return null !== $taxon->getParent();
        }

        for ($parent = $taxon->getParent(); null !== $parent; $parent = $parent->getParent()) {
            if ($parent->getCode() === $menuTaxon->getCode()) {
                return true;
            }
        }

        return false;
    }

    public function all(ChannelInterface $channel): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('taxon')
            ->from($this->taxonClass, 'taxon')
            ->orderBy('taxon.root')
            ->addOrderBy('taxon.left')
        ;

        $menuTaxon = $channel->getMenuTaxon();
        if (null === $menuTaxon) {
            $queryBuilder->andWhere('taxon.parent IS NOT NULL');
        } else {
            $queryBuilder
                ->andWhere('taxon.root = :root')
                ->andWhere('taxon.left > :left')
                ->andWhere('taxon.right < :right')
                ->setParameter('root', $menuTaxon->getRoot() ?? $menuTaxon)
                ->setParameter('left', $menuTaxon->getLeft())
                ->setParameter('right', $menuTaxon->getRight())
            ;
        }

        /** @var list<TaxonInterface> $taxons */
        $taxons = $queryBuilder->getQuery()->getResult();

        return $taxons;
    }
}
