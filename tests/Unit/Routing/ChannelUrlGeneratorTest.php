<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Routing;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGenerator;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class ChannelUrlGeneratorTest extends TestCase
{
    private RequestContext $context;

    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->context = new RequestContext(host: 'localhost', scheme: 'http', httpPort: 8090, httpsPort: 8443);

        $routes = new RouteCollection();
        $routes->add('product', new Route('/products/{slug}'));
        $this->urlGenerator = new UrlGenerator($routes, $this->context);
    }

    public function testItUsesHttpsOnTheChannelHostAndRestoresTheContext(): void
    {
        $generator = new ChannelUrlGenerator($this->urlGenerator, $this->createStub(CacheManager::class), 'filter');

        self::assertSame('https://shop.example.com/products/t-shirt', $generator->generate($this->channel('shop.example.com'), 'product', ['slug' => 't-shirt']));
        self::assertSame('http://localhost:8090/products/t-shirt', $this->urlGenerator->generate('product', ['slug' => 't-shirt'], UrlGeneratorInterface::ABSOLUTE_URL));
        self::assertSame(8443, $this->context->getHttpsPort());
    }

    public function testItKeepsSchemeAndPortWhenTheChannelHostIsTheCurrentOne(): void
    {
        $generator = new ChannelUrlGenerator($this->urlGenerator, $this->createStub(CacheManager::class), 'filter');

        self::assertSame('http://localhost:8090/products/t-shirt', $generator->generate($this->channel('LocalHost'), 'product', ['slug' => 't-shirt']));
    }

    public function testItKeepsTheCurrentContextWhenTheChannelHasNoHost(): void
    {
        $generator = new ChannelUrlGenerator($this->urlGenerator, $this->createStub(CacheManager::class), 'filter');

        self::assertSame('http://localhost:8090/products/t-shirt', $generator->generate($this->channel(null), 'product', ['slug' => 't-shirt']));
    }

    public function testItGeneratesImageUrlsWithTheConfiguredOrGivenFilter(): void
    {
        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager
            ->expects(self::exactly(2))
            ->method('getBrowserPath')
            ->willReturnCallback(fn (string $path, string $filter): string => sprintf('%s://%s/media/cache/%s/%s', $this->context->getScheme(), $this->context->getHost(), $filter, $path))
        ;

        $generator = new ChannelUrlGenerator($this->urlGenerator, $cacheManager, 'default_filter');
        $channel = $this->channel('shop.example.com');

        self::assertSame('https://shop.example.com/media/cache/default_filter/a/b.jpg', $generator->generateImageUrl($channel, 'a/b.jpg'));
        self::assertSame('https://shop.example.com/media/cache/small/a/b.jpg', $generator->generateImageUrl($channel, 'a/b.jpg', 'small'));
        self::assertSame('localhost', $this->context->getHost());
    }

    public function testItRestoresTheContextWhenGenerationFails(): void
    {
        $generator = new ChannelUrlGenerator($this->urlGenerator, $this->createStub(CacheManager::class), 'filter');

        try {
            $generator->generate($this->channel('shop.example.com'), 'missing');
            self::fail('Expected an exception.');
        } catch (\Throwable) {
        }

        self::assertSame(['localhost', 'http', 8443], [$this->context->getHost(), $this->context->getScheme(), $this->context->getHttpsPort()]);
    }

    private function channel(?string $hostname): Channel
    {
        $channel = new Channel();
        $channel->setHostname($hostname);

        return $channel;
    }
}
