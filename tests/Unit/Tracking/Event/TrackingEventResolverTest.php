<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Tracking\Event;

use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\EventPropertiesProviderInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventResolver;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventSettings;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventSide;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\Product;

final class TrackingEventResolverTest extends TestCase
{
    private Channel $channel;

    protected function setUp(): void
    {
        $this->channel = new Channel();
        $this->channel->setCode('WEB');
    }

    public function testItBuildsTheEventWithItsProvidersAndName(): void
    {
        $provider = new class() implements EventPropertiesProviderInterface {
            public function provide(string $eventCode, object $subject, ChannelInterface $channel): array
            {
                return ['brand' => 'Odiseo', 'name' => 'Renamed', 'stock' => null];
            }
        };

        $event = $this->resolver(['product_viewed' => ['enabled' => true, 'name' => 'viewed-product']], [$provider])
            ->resolve('product_viewed', new Product(), $this->channel);

        self::assertSame('viewed-product', $event?->name);
        self::assertSame(['name' => 'Renamed', 'sku' => 'PHP_T_SHIRT', 'brand' => 'Odiseo'], $event->properties);
    }

    public function testNothingIsTrackedWhenOffUnknownOrUnsupported(): void
    {
        self::assertNull($this->resolver(['product_viewed' => ['enabled' => false, 'name' => null]])->resolve('product_viewed', new Product(), $this->channel));
        self::assertNull($this->resolver()->resolve('cart_updated', new Product(), $this->channel));
        self::assertNull($this->resolver()->resolve('product_viewed', new \stdClass(), $this->channel));
        self::assertNull($this->resolver(modules: ['contacts'])->resolve('product_viewed', new Product(), $this->channel));
        self::assertNotNull($this->resolver()->resolve('product_viewed', new Product(), $this->channel));
    }

    public function testItListsTheEventsByCode(): void
    {
        self::assertSame(['product_viewed'], array_keys($this->resolver()->getEvents()));
    }

    /**
     * @param array<string, array{enabled: bool, name: string|null}> $events
     * @param list<EventPropertiesProviderInterface> $providers
     * @param list<string> $modules
     */
    private function resolver(array $events = [], array $providers = [], array $modules = ['tracking']): TrackingEventResolver
    {
        $configurationProvider = $this->createStub(ConfigurationProviderInterface::class);
        $configurationProvider->method('getSettings')->willReturn(new BrevoSettings('WEB', new Credentials('key'), modules: $modules));

        $productViewed = new class() implements TrackingEventInterface {
            public function getCode(): string
            {
                return 'product_viewed';
            }

            public function getSide(): TrackingEventSide
            {
                return TrackingEventSide::Browser;
            }

            public function supports(object $subject): bool
            {
                return $subject instanceof Product;
            }

            public function getProperties(object $subject, ChannelInterface $channel): array
            {
                return ['name' => 'PHP T-Shirt', 'sku' => 'PHP_T_SHIRT'];
            }
        };

        return new TrackingEventResolver([$productViewed], $providers, new TrackingEventSettings($events), $configurationProvider);
    }
}
