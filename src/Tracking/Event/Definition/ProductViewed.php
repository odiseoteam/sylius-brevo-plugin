<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition;

use Odiseo\SyliusBrevoPlugin\Catalog\Product\ProductImagePath;
use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatterInterface;
use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventSide;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Webmozart\Assert\Assert;

/** A product page was shown; priced with its default variant in the channel's currency. */
final class ProductViewed implements TrackingEventInterface
{
    public const CODE = 'product_viewed';

    public function __construct(
        private readonly ProductVariantResolverInterface $variantResolver,
        private readonly MoneyFormatterInterface $moneyFormatter,
        private readonly ChannelUrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getSide(): TrackingEventSide
    {
        return TrackingEventSide::Browser;
    }

    public function supports(object $subject): bool
    {
        return $subject instanceof ProductInterface;
    }

    public function getProperties(object $subject, ChannelInterface $channel): array
    {
        Assert::isInstanceOf($subject, ProductInterface::class);

        $localeCode = $channel->getDefaultLocale()?->getCode();
        $translation = $subject->getTranslation($localeCode);
        $slug = $translation->getSlug();
        $variant = $this->variantResolver->getVariant($subject);
        $variant = $variant instanceof ProductVariantInterface ? $variant : null;
        $price = $variant?->getChannelPricingForChannel($channel)?->getPrice();
        $currency = $channel->getBaseCurrency()?->getCode();
        $image = ProductImagePath::of($variant, $subject);

        return [
            'product_id' => $subject->getCode(),
            'name' => $translation->getName(),
            'price' => null === $price || null === $currency ? null : $this->moneyFormatter->format($price, $currency),
            'currency' => $currency,
            'url' => null === $slug || null === $localeCode ? null : $this->urlGenerator->generate($channel, 'sylius_shop_product_show', [
                'slug' => $slug,
                '_locale' => $localeCode,
            ]),
            'image' => null === $image ? null : $this->urlGenerator->generateImageUrl($channel, $image),
        ];
    }
}
