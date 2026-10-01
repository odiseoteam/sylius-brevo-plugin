<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Catalog;

use Odiseo\SyliusBrevoPlugin\Catalog\CatalogTargetResolver;
use Odiseo\SyliusBrevoPlugin\Catalog\ChannelTaxonsInterface;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Ecommerce\EcommerceAccountsInterface;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Currency\Model\Currency;

final class CatalogTargetResolverTest extends TestCase
{
    public function testAProductIsReadFromAChannelSellingItInTheAccountCurrency(): void
    {
        $uk = $this->channel('UK', 'GBP');
        $us = $this->channel('US', 'USD');
        $ar = $this->channel('AR', 'ARS');
        $resolver = $this->resolver();

        $product = new Product();
        $product->addChannel($uk);
        $product->addChannel($us);
        self::assertSame($us, $resolver->productChannel($product, [$ar, $uk, $us]));

        $product->removeChannel($us);
        self::assertSame($uk, $resolver->productChannel($product, [$ar, $uk, $us]));

        $product->removeChannel($uk);
        self::assertSame($ar, $resolver->productChannel($product, [$ar, $uk, $us]));
    }

    private function resolver(): CatalogTargetResolver
    {
        $accounts = $this->createStub(EcommerceAccountsInterface::class);
        $accounts->method('currencyOf')->willReturn('USD');

        return new CatalogTargetResolver(
            $this->createStub(ChannelConfigurationRepositoryInterface::class),
            $this->createStub(ConfigurationProviderInterface::class),
            $this->createStub(ChannelTaxonsInterface::class),
            $accounts,
        );
    }

    private function channel(string $code, string $currencyCode): Channel
    {
        $currency = new Currency();
        $currency->setCode($currencyCode);
        $channel = new Channel();
        $channel->setCode($code);
        $channel->setBaseCurrency($currency);

        return $channel;
    }
}
