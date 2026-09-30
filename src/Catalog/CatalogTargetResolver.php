<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;

final class CatalogTargetResolver implements CatalogTargetResolverInterface
{
    public function __construct(
        private readonly ChannelConfigurationRepositoryInterface $configurationRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ChannelTaxonsInterface $channelTaxons,
    ) {
    }

    public function accounts(): array
    {
        return array_values(array_map(static fn (array $channels): ChannelInterface => $channels[0], $this->groupByAccount()));
    }

    public function channelsByAccount(): array
    {
        return array_values($this->groupByAccount());
    }

    public function resolveTaxon(TaxonInterface $taxon): array
    {
        return $this->pick(fn (ChannelInterface $channel): bool => $this->channelTaxons->contains($channel, $taxon));
    }

    public function resolveProduct(ProductInterface $product): array
    {
        return $this->pick(static fn (ChannelInterface $channel): bool => $product->hasChannel($channel));
    }

    /**
     * @param callable(ChannelInterface): bool $shows
     *
     * @return list<ChannelInterface>
     */
    private function pick(callable $shows): array
    {
        $targets = [];
        foreach ($this->groupByAccount() as $channels) {
            $showing = array_filter($channels, $shows);
            $targets[] = [] === $showing ? $channels[0] : reset($showing);
        }

        return $targets;
    }

    /** @return array<string, non-empty-list<ChannelInterface>> */
    private function groupByAccount(): array
    {
        $groups = [];
        /** @var ChannelConfigurationInterface $configuration */
        foreach ($this->configurationRepository->findBy(['enabled' => true], ['id' => 'ASC']) as $configuration) {
            $channel = $configuration->getChannel();
            if (!$channel instanceof ChannelInterface) {
                continue;
            }

            $settings = $this->configurationProvider->getSettings($channel);
            if (null !== $settings && $settings->hasModule(CatalogModule::CODE)) {
                $groups[$settings->credentials->apiKey][] = $channel;
            }
        }

        return $groups;
    }
}
