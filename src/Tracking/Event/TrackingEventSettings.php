<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

use Sylius\Component\Core\Model\ChannelInterface;

/** From `odiseo_sylius_brevo.tracking.events`, the same for every channel; unlisted events are on. */
final class TrackingEventSettings implements TrackingEventSettingsInterface
{
    /** @param array<string, array{enabled: bool, name: string|null}> $events */
    public function __construct(private readonly array $events)
    {
    }

    public function isEnabled(string $eventCode, ChannelInterface $channel): bool
    {
        return $this->events[$eventCode]['enabled'] ?? true;
    }

    public function getName(string $eventCode, ChannelInterface $channel): string
    {
        return $this->events[$eventCode]['name'] ?? $eventCode;
    }
}
