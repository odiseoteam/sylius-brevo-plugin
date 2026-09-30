<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Odiseo\SyliusBrevoPlugin\Catalog\CatalogTargetResolverInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\Message\SyncProducts;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductTranslationInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Collects variants touched by a flush (themselves, their product, prices, images, taxons or
 * translations) and sends them, one message per Brevo account, once the flush is done.
 */
final class ProductChangesListener implements ResetInterface
{
    /** @var array<int, ProductVariantInterface> */
    private array $changed = [];

    /** @var array<int, array{variant: ProductVariantInterface, product: ProductInterface}> */
    private array $deleted = [];

    public function __construct(
        private readonly CatalogTargetResolverInterface $targetResolver,
        private readonly BrevoMessageDispatcherInterface $dispatcher,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        $entities = [
            ...$unitOfWork->getScheduledEntityInsertions(),
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
        ];
        foreach ([...$unitOfWork->getScheduledCollectionUpdates(), ...$unitOfWork->getScheduledCollectionDeletions()] as $collection) {
            $owner = $collection->getOwner();
            if (null !== $owner) {
                $entities[] = $owner;
            }
        }

        foreach ($entities as $entity) {
            foreach ($this->variantsOf($entity) as $variant) {
                $this->changed[spl_object_id($variant)] = $variant;
            }
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $variants = match (true) {
                $entity instanceof ProductVariantInterface => [$entity],
                $entity instanceof ProductInterface => $entity->getVariants(),
                default => [],
            };
            foreach ($variants as $variant) {
                // A removed variant is usually detached from its product already.
                $product = $variant->getProduct() ?? $unitOfWork->getOriginalEntityData($variant)['product'] ?? null;
                if ($variant instanceof ProductVariantInterface && null !== $variant->getCode() && $product instanceof ProductInterface) {
                    $this->deleted[spl_object_id($variant)] = ['variant' => $variant, 'product' => $product];
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $changed = array_diff_key($this->changed, $this->deleted);
        $deleted = $this->deleted;
        $this->reset();

        /** @var array<string, array{codes?: list<string>, deleted?: array<string, string>}> $byChannel */
        $byChannel = [];
        foreach ($changed as $variant) {
            $product = $variant->getProduct();
            if (!$product instanceof ProductInterface || null === $variant->getCode()) {
                continue;
            }
            foreach ($this->targetResolver->resolveProduct($product) as $channel) {
                $byChannel[(string) $channel->getCode()]['codes'][] = $variant->getCode();
            }
        }

        foreach ($deleted as ['variant' => $variant, 'product' => $product]) {
            foreach ($this->targetResolver->resolveProduct($product) as $channel) {
                $byChannel[(string) $channel->getCode()]['deleted'][(string) $variant->getCode()] = $product->getName() ?? (string) $variant->getCode();
            }
        }

        foreach ($byChannel as $channelCode => $variants) {
            $this->dispatcher->dispatch(new SyncProducts($channelCode, array_values(array_unique($variants['codes'] ?? [])), $variants['deleted'] ?? []));
        }
    }

    public function reset(): void
    {
        $this->changed = [];
        $this->deleted = [];
    }

    /** @return iterable<ProductVariantInterface> */
    private function variantsOf(object $entity): iterable
    {
        $product = match (true) {
            $entity instanceof ProductVariantInterface => null,
            $entity instanceof ProductInterface => $entity,
            $entity instanceof ProductTranslationInterface => $entity->getTranslatable(),
            $entity instanceof ProductTaxonInterface => $entity->getProduct(),
            $entity instanceof ProductImageInterface => $entity->getOwner(),
            default => null,
        };

        $variant = match (true) {
            $entity instanceof ProductVariantInterface => $entity,
            $entity instanceof ProductVariantTranslationInterface => $entity->getTranslatable(),
            $entity instanceof ChannelPricingInterface => $entity->getProductVariant(),
            default => null,
        };

        if ($variant instanceof ProductVariantInterface) {
            yield $variant;
        }

        if ($product instanceof ProductInterface) {
            foreach ($product->getVariants() as $productVariant) {
                if ($productVariant instanceof ProductVariantInterface) {
                    yield $productVariant;
                }
            }
        }
    }
}
