<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Client\Model\CategoryData;
use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;

final class CategoryPayloadBuilder implements CategoryPayloadBuilderInterface
{
    public function __construct(
        private readonly ChannelTaxonsInterface $channelTaxons,
        private readonly ChannelUrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function build(TaxonInterface $taxon, ChannelInterface $channel): CategoryData
    {
        $code = (string) $taxon->getCode();
        $localeCode = $channel->getDefaultLocale()?->getCode();
        $translation = $taxon->getTranslation($localeCode);
        $slug = $translation->getSlug();

        if (!$taxon->isEnabled() || !$this->channelTaxons->contains($channel, $taxon)) {
            return new CategoryData($code, $translation->getName() ?? $code, deleted: true);
        }

        $url = null === $slug || null === $localeCode ? null : $this->urlGenerator->generate($channel, 'sylius_shop_product_index', [
            'slug' => $slug,
            '_locale' => $localeCode,
        ]);

        return new CategoryData($code, $this->path($taxon, $channel, $localeCode), $url);
    }

    /** Names from the first level under the menu (or the root), e.g. "T-shirts > Men". */
    private function path(TaxonInterface $taxon, ChannelInterface $channel, ?string $localeCode): string
    {
        $stop = $channel->getMenuTaxon()?->getCode();
        $names = [];
        for ($current = $taxon; null !== $current->getParent() && $current->getCode() !== $stop; $current = $current->getParent()) {
            array_unshift($names, $current->getTranslation($localeCode)->getName() ?? (string) $current->getCode());
        }

        return implode(' > ', $names);
    }
}
