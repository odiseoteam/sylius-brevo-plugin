<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition;

use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventSide;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Webmozart\Assert\Assert;

/** A taxon's product listing was shown. */
final class CategoryViewed implements TrackingEventInterface
{
    public const CODE = 'category_viewed';

    public function __construct(private readonly ChannelUrlGeneratorInterface $urlGenerator)
    {
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
        return $subject instanceof TaxonInterface;
    }

    public function getProperties(object $subject, ChannelInterface $channel): array
    {
        Assert::isInstanceOf($subject, TaxonInterface::class);

        $localeCode = $channel->getDefaultLocale()?->getCode();
        $translation = $subject->getTranslation($localeCode);
        $slug = $translation->getSlug();

        return [
            'category_id' => $subject->getCode(),
            'name' => $translation->getName(),
            'url' => null === $slug || null === $localeCode ? null : $this->urlGenerator->generate($channel, 'sylius_shop_product_index', [
                'slug' => $slug,
                '_locale' => $localeCode,
            ]),
        ];
    }
}
