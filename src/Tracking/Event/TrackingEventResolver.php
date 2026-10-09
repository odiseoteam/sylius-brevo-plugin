<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackingModule;
use Sylius\Component\Core\Model\ChannelInterface;

final class TrackingEventResolver implements TrackingEventResolverInterface
{
    /** @var array<string, TrackingEventInterface> */
    private array $events = [];

    /**
     * @param iterable<TrackingEventInterface> $events later ones replace earlier ones with the same code
     * @param iterable<EventPropertiesProviderInterface> $propertiesProviders
     */
    public function __construct(
        iterable $events,
        private readonly iterable $propertiesProviders,
        private readonly TrackingEventSettingsInterface $settings,
        private readonly ConfigurationProviderInterface $configurationProvider,
    ) {
        foreach ($events as $event) {
            $this->events[$event->getCode()] = $event;
        }
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function resolve(string $eventCode, object $subject, ChannelInterface $channel): ?ResolvedTrackingEvent
    {
        $event = $this->events[$eventCode] ?? null;
        if (
            null === $event ||
            !$event->supports($subject) ||
            !$this->settings->isEnabled($eventCode, $channel) ||
            true !== $this->configurationProvider->getSettings($channel)?->hasModule(TrackingModule::CODE)
        ) {
            return null;
        }

        $properties = $event->getProperties($subject, $channel);
        foreach ($this->propertiesProviders as $provider) {
            $properties = [...$properties, ...$provider->provide($eventCode, $subject, $channel)];
        }

        return new ResolvedTrackingEvent(
            $this->settings->getName($eventCode, $channel),
            array_filter($properties, static fn (mixed $value): bool => null !== $value),
        );
    }
}
