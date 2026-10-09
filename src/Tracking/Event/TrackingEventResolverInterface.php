<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

use Sylius\Component\Core\Model\ChannelInterface;

interface TrackingEventResolverInterface
{
    /** @return array<string, TrackingEventInterface> by code */
    public function getEvents(): array;

    /** Null when the channel doesn't track it (no tracking module, event off, unknown or unsupported subject). */
    public function resolve(string $eventCode, object $subject, ChannelInterface $channel): ?ResolvedTrackingEvent;
}
