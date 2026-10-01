<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Catalog;

use Doctrine\ORM\EntityManagerInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\ChannelTaxons;
use Odiseo\SyliusBrevoPlugin\Catalog\Product\ProductCategoriesProvider;
use Odiseo\SyliusBrevoPlugin\Catalog\Product\ProductDetailsProvider;
use Odiseo\SyliusBrevoPlugin\Catalog\Product\ProductPayloadProviderInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\Product\ProductPriceProvider;
use Odiseo\SyliusBrevoPlugin\Catalog\ProductPayloadBuilder;
use Odiseo\SyliusBrevoPlugin\Ecommerce\AccountMoneyFormatterInterface;
use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatter;
use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductImage;
use Sylius\Component\Core\Model\ProductTaxon;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Sylius\Component\Product\Model\ProductOption;
use Sylius\Component\Product\Model\ProductOptionValue;

final class ProductPayloadBuilderTest extends TestCase
{
    private Channel $channel;

    private Taxon $men;

    private Product $product;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        $menu = $this->taxon('menu', 'Menu');
        $shirts = $this->taxon('t_shirts', 'T-shirts', $menu);
        $this->men = $this->taxon('men', 'Men', $shirts);

        $this->channel = new Channel();
        $this->channel->setCode('WEB');
        $locale = new Locale();
        $locale->setCode('en_US');
        $this->channel->setDefaultLocale($locale);
        $currency = new Currency();
        $currency->setCode('USD');
        $this->channel->setBaseCurrency($currency);
        $this->channel->setMenuTaxon($menu);

        $this->product = new Product();
        $this->product->setCurrentLocale('en_US');
        $this->product->setFallbackLocale('en_US');
        $this->product->setCode('tee');
        $this->product->setName('Tee');
        $this->product->setSlug('tee');
        $this->product->setShortDescription('<p>Soft   cotton &amp; more</p>');
        $this->product->addChannel($this->channel);
        $productTaxon = new ProductTaxon();
        $productTaxon->setTaxon($this->men);
        $this->product->addProductTaxon($productTaxon);
        $image = new ProductImage();
        $image->setType('main');
        $image->setPath('ab/tee.jpg');
        $this->product->addImage($image);

        $this->variant = $this->variant('tee_m', 'M', 2500);
    }

    public function testAVariantSoldInTheChannelHasItsFields(): void
    {
        $this->variant->getChannelPricingForChannel($this->channel)?->setOriginalPrice(3000);
        $this->variant->setTracked(true);
        $this->variant->setOnHand(5);
        $this->variant->setOnHold(2);

        self::assertSame([
            'id' => 'tee_m',
            'name' => 'Tee',
            'parentId' => 'tee',
            'sku' => 'tee_m',
            'url' => 'https://shop.example.com/sylius_shop_product_show/tee',
            'imageUrl' => 'https://shop.example.com/media/ab/tee.jpg',
            'description' => 'Soft cotton & more',
            'price' => 25.0,
            'alternativePrice' => 30.0,
            'stock' => 3,
            'categories' => ['men', 't_shirts'],
            'metaInfo' => ['size' => 'M'],
            'isDeleted' => false,
        ], $this->builder()->build($this->variant, $this->channel)->toArray());
    }

    public function testASingleVariantProductSharingItsCodeHasNoParentAndUntrackedStockIsLeftOut(): void
    {
        $product = new Product();
        $product->setCurrentLocale('en_US');
        $product->setFallbackLocale('en_US');
        $product->setCode('cap');
        $product->setName('Cap');
        $product->addChannel($this->channel);
        $variant = new ProductVariant();
        $variant->setCode('cap');
        $product->addVariant($variant);
        $pricing = new ChannelPricing();
        $pricing->setChannelCode('WEB');
        $pricing->setPrice(1000);
        $variant->addChannelPricing($pricing);

        self::assertSame(['id' => 'cap', 'name' => 'Cap', 'sku' => 'cap', 'price' => 10.0, 'categories' => [], 'isDeleted' => false], $this->builder()->build($variant, $this->channel)->toArray());
    }

    public function testMoreVariantsAddTheVariantNameAndProvidersMergeMetaInfo(): void
    {
        $this->variant('tee_l', 'L', 2500);
        $extra = new class() implements ProductPayloadProviderInterface {
            public function provide(\Sylius\Component\Core\Model\ProductVariantInterface $variant, ChannelInterface $channel): array
            {
                return ['brand' => 'Acme', 'metaInfo' => ['material' => 'cotton']];
            }
        };

        $data = $this->builder($extra)->build($this->variant, $this->channel);

        self::assertSame('Acme', $data->fields['brand'] ?? null);
        self::assertSame(['variant' => 'Tee M', 'size' => 'M', 'material' => 'cotton'], $data->fields['metaInfo'] ?? null);
    }

    public function testAVariantNotSoldInTheChannelIsDeleted(): void
    {
        $builder = $this->builder();

        $this->variant->setEnabled(false);
        self::assertSame(['id' => 'tee_m', 'name' => 'Tee', 'isDeleted' => true], $builder->build($this->variant, $this->channel)->toArray());

        $this->variant->setEnabled(true);
        $this->product->setEnabled(false);
        self::assertTrue($builder->build($this->variant, $this->channel)->deleted);

        $this->product->setEnabled(true);
        $this->product->removeChannel($this->channel);
        self::assertTrue($builder->build($this->variant, $this->channel)->deleted);
    }

    private function builder(ProductPayloadProviderInterface ...$extra): ProductPayloadBuilder
    {
        $urlGenerator = $this->createStub(ChannelUrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (ChannelInterface $channel, string $route, array $parameters): string => 'https://shop.example.com/' . $route . '/' . (is_string($parameters['slug'] ?? null) ? $parameters['slug'] : ''),
        );
        $urlGenerator->method('generateImageUrl')->willReturnCallback(static fn (ChannelInterface $channel, string $path): string => 'https://shop.example.com/media/' . $path);
        $moneyFormatter = $this->createStub(AccountMoneyFormatterInterface::class);
        $moneyFormatter->method('format')->willReturnCallback(static fn (int $amount, string $currencyCode): float => (new MoneyFormatter())->format($amount, $currencyCode));
        $channelTaxons = new ChannelTaxons($this->createStub(EntityManagerInterface::class), Taxon::class);

        return new ProductPayloadBuilder([
            new ProductDetailsProvider($urlGenerator),
            new ProductPriceProvider($moneyFormatter),
            new ProductCategoriesProvider($channelTaxons),
            ...$extra,
        ]);
    }

    private function variant(string $code, string $size, int $price): ProductVariant
    {
        $option = new ProductOption();
        $option->setCode('size');
        $value = new ProductOptionValue();
        $value->setCurrentLocale('en_US');
        $value->setFallbackLocale('en_US');
        $value->setOption($option);
        $value->setCode($code . '_size');
        $value->setValue($size);

        $variant = new ProductVariant();
        $variant->setCurrentLocale('en_US');
        $variant->setFallbackLocale('en_US');
        $variant->setCode($code);
        $variant->setName('Tee ' . $size);
        $variant->addOptionValue($value);
        $this->product->addVariant($variant);

        $pricing = new ChannelPricing();
        $pricing->setChannelCode('WEB');
        $pricing->setPrice($price);
        $variant->addChannelPricing($pricing);

        return $variant;
    }

    private function taxon(string $code, string $name, ?Taxon $parent = null): Taxon
    {
        $taxon = new Taxon();
        $taxon->setCurrentLocale('en_US');
        $taxon->setFallbackLocale('en_US');
        $taxon->setCode($code);
        $taxon->setName($name);
        $parent?->addChild($taxon);

        return $taxon;
    }
}
