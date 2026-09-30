<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Catalog;

use Doctrine\ORM\EntityManagerInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\CategoryPayloadBuilder;
use Odiseo\SyliusBrevoPlugin\Catalog\ChannelTaxons;
use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Locale\Model\Locale;

final class CategoryPayloadBuilderTest extends TestCase
{
    private Taxon $menu;

    private Taxon $caps;

    private Taxon $other;

    private CategoryPayloadBuilder $builder;

    protected function setUp(): void
    {
        $this->menu = $this->taxon('menu', 'Menu', 'menu');
        $this->caps = $this->taxon('caps', 'Caps', 'menu/caps', $this->menu);
        $this->other = $this->taxon('other', 'Other', 'other');

        $urlGenerator = $this->createStub(ChannelUrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (ChannelInterface $channel, string $route, array $parameters): string => 'https://shop.example.com/' . implode('/taxons/', array_map('strval', array_filter([$parameters['_locale'] ?? null, $parameters['slug'] ?? null], 'is_string'))),
        );

        $this->builder = new CategoryPayloadBuilder(new ChannelTaxons($this->createStub(EntityManagerInterface::class), Taxon::class), $urlGenerator);
    }

    public function testATaxonOfTheChannelLinksToItsPage(): void
    {
        $category = $this->builder->build($this->caps, $this->channel($this->menu));

        self::assertSame(['id' => 'caps', 'name' => 'Caps', 'url' => 'https://shop.example.com/en_US/taxons/menu/caps', 'isDeleted' => false], $category->toArray());
    }

    public function testANestedTaxonIsNamedWithItsPath(): void
    {
        $men = $this->taxon('men', 'Men', 'menu/caps/men', $this->caps);

        self::assertSame('Caps > Men', $this->builder->build($men, $this->channel($this->menu))->name);
        self::assertSame('Caps > Men', $this->builder->build($men, $this->channel(null))->name);
    }

    public function testADisabledTaxonOrOneOutsideTheMenuIsDeleted(): void
    {
        $channel = $this->channel($this->menu);
        $this->caps->setEnabled(false);

        self::assertTrue($this->builder->build($this->caps, $channel)->deleted);
        self::assertTrue($this->builder->build($this->other, $channel)->deleted);
        self::assertTrue($this->builder->build($this->menu, $channel)->deleted);
    }

    public function testWithoutAMenuTaxonEveryNonRootTaxonIsShown(): void
    {
        $channel = $this->channel(null);

        self::assertFalse($this->builder->build($this->caps, $channel)->deleted);
        self::assertTrue($this->builder->build($this->other, $channel)->deleted);
    }

    private function channel(?Taxon $menuTaxon): Channel
    {
        $locale = new Locale();
        $locale->setCode('en_US');

        $channel = new Channel();
        $channel->setDefaultLocale($locale);
        $channel->setMenuTaxon($menuTaxon);

        return $channel;
    }

    private function taxon(string $code, string $name, string $slug, ?Taxon $parent = null): Taxon
    {
        $taxon = new Taxon();
        $taxon->setCurrentLocale('en_US');
        $taxon->setFallbackLocale('en_US');
        $taxon->setCode($code);
        $taxon->setName($name);
        $taxon->setSlug($slug);
        $parent?->addChild($taxon);

        return $taxon;
    }
}
