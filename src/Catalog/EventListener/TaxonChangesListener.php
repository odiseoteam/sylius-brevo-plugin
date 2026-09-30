<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Odiseo\SyliusBrevoPlugin\Catalog\CatalogTargetResolverInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\Message\SyncCategories;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Collects taxons touched by a flush (themselves or a translation; a moved or renamed one with its subtree) and
 * sends them, one message per Brevo account, once the flush is done.
 */
final class TaxonChangesListener implements ResetInterface
{
    /** @var array<int, TaxonInterface> */
    private array $changed = [];

    /** @var array<int, TaxonInterface> */
    private array $deleted = [];

    public function __construct(
        private readonly CatalogTargetResolverInterface $targetResolver,
        private readonly BrevoMessageDispatcherInterface $dispatcher,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ([...$unitOfWork->getScheduledEntityInsertions(), ...$unitOfWork->getScheduledEntityUpdates()] as $entity) {
            $taxon = match (true) {
                $entity instanceof TaxonInterface => $entity,
                $entity instanceof TaxonTranslationInterface => $entity->getTranslatable(),
                default => null,
            };
            if (!$taxon instanceof TaxonInterface) {
                continue;
            }

            $this->changed[spl_object_id($taxon)] = $taxon;
            $changeSet = $unitOfWork->getEntityChangeSet($entity);
            if (array_key_exists($entity === $taxon ? 'parent' : 'name', $changeSet)) {
                $this->addDescendants($taxon);
            }
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof TaxonInterface && null !== $entity->getCode()) {
                $this->deleted[spl_object_id($entity)] = $entity;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $changed = array_diff_key($this->changed, $this->deleted);
        $deleted = $this->deleted;
        $this->reset();

        /** @var array<string, array{codes: list<string>, deleted: array<string, string>}> $byChannel */
        $byChannel = [];
        foreach ($changed as $taxon) {
            foreach ($this->targetResolver->resolve($taxon) as $channel) {
                $byChannel[(string) $channel->getCode()]['codes'][] = (string) $taxon->getCode();
            }
        }

        foreach ($deleted as $taxon) {
            foreach ($this->targetResolver->resolve($taxon) as $channel) {
                $byChannel[(string) $channel->getCode()]['deleted'][(string) $taxon->getCode()] = $taxon->getName() ?? (string) $taxon->getCode();
            }
        }

        foreach ($byChannel as $channelCode => $taxons) {
            $this->dispatcher->dispatch(new SyncCategories($channelCode, array_values(array_unique($taxons['codes'] ?? [])), $taxons['deleted'] ?? []));
        }
    }

    public function reset(): void
    {
        $this->changed = [];
        $this->deleted = [];
    }

    private function addDescendants(TaxonInterface $taxon): void
    {
        foreach ($taxon->getChildren() as $child) {
            if ($child instanceof TaxonInterface) {
                $this->changed[spl_object_id($child)] = $child;
                $this->addDescendants($child);
            }
        }
    }
}
