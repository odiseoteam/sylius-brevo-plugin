<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Product;

use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/** sku, url, imageUrl, description, and the variant name and options as metaInfo. */
final class ProductDetailsProvider implements ProductPayloadProviderInterface
{
    private const DESCRIPTION_LENGTH = 1000;

    public function __construct(private readonly ChannelUrlGeneratorInterface $urlGenerator)
    {
    }

    public function provide(ProductVariantInterface $variant, ChannelInterface $channel): array
    {
        $product = $variant->getProduct();
        if (!$product instanceof ProductInterface) {
            return [];
        }

        $localeCode = $channel->getDefaultLocale()?->getCode();
        $translation = $product->getTranslation($localeCode);
        $slug = $translation->getSlug();
        $image = ProductImagePath::of($variant, $product);

        $metaInfo = [];
        if ($product->getVariants()->count() > 1) {
            $metaInfo['variant'] = $variant->getTranslation($localeCode)->getName();
        }
        foreach ($variant->getOptionValues() as $optionValue) {
            $metaInfo[(string) $optionValue->getOptionCode()] = $optionValue->getTranslation($localeCode)->getValue();
        }

        return [
            'sku' => $variant->getCode(),
            'url' => null === $slug || null === $localeCode ? null : $this->urlGenerator->generate($channel, 'sylius_shop_product_show', [
                'slug' => $slug,
                '_locale' => $localeCode,
            ]),
            'imageUrl' => null === $image ? null : $this->urlGenerator->generateImageUrl($channel, $image),
            'description' => $this->description($translation->getShortDescription() ?? $translation->getDescription()),
            'metaInfo' => $metaInfo,
        ];
    }

    private function description(?string $html): ?string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $html)));

        return '' === $text ? null : mb_substr(html_entity_decode($text), 0, self::DESCRIPTION_LENGTH);
    }
}
