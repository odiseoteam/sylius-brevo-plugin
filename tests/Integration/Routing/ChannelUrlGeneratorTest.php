<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Integration\Routing;

use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use Sylius\Component\Core\Model\Channel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ChannelUrlGeneratorTest extends KernelTestCase
{
    public function testItBuildsShopAndImageUrlsOnTheChannelHost(): void
    {
        self::bootKernel();

        $generator = self::getContainer()->get(ChannelUrlGeneratorInterface::class);
        self::assertInstanceOf(ChannelUrlGeneratorInterface::class, $generator);

        $channel = new Channel();
        $channel->setHostname('shop.example.com');

        self::assertSame(
            'https://shop.example.com/en_US/products/t-shirt',
            $generator->generate($channel, 'sylius_shop_product_show', ['_locale' => 'en_US', 'slug' => 't-shirt']),
        );
        self::assertStringStartsWith(
            'https://shop.example.com/media/cache/',
            $generator->generateImageUrl($channel, 'ab/cd/t-shirt.jpg'),
        );
        self::assertStringContainsString('odiseo_brevo_product/ab/cd/t-shirt.jpg', $generator->generateImageUrl($channel, 'ab/cd/t-shirt.jpg'));
    }

    public function testItKeepsTheDefaultUriForTheLocalChannel(): void
    {
        self::bootKernel();

        $generator = self::getContainer()->get(ChannelUrlGeneratorInterface::class);
        self::assertInstanceOf(ChannelUrlGeneratorInterface::class, $generator);

        $router = self::getContainer()->get('router');
        self::assertInstanceOf(UrlGeneratorInterface::class, $router);

        $parameters = ['_locale' => 'en_US', 'slug' => 't-shirt'];
        $expected = $router->generate('sylius_shop_product_show', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

        $channel = new Channel();
        $channel->setHostname($router->getContext()->getHost());

        self::assertStringStartsWith('http://localhost', $expected);
        self::assertSame($expected, $generator->generate($channel, 'sylius_shop_product_show', $parameters));
    }
}
