<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

use Sylius\Component\Core\Model\ChannelInterface;

/** Adds properties to tracking events. Tag: `odiseo_brevo.event_payload_provider`. */
interface EventPropertiesProviderInterface
{
    /**
     * Null means unknown: it's not sent. Wins over the event's own properties and earlier providers.
     *
     * @return array<string, mixed>
     */
    public function provide(string $eventCode, object $subject, ChannelInterface $channel): array;
}
