<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

use Sylius\Component\Core\Model\ChannelInterface;

/** An event sent to Brevo. Tag: `odiseo_brevo.tracking_event`. */
interface TrackingEventInterface
{
    /** Also its default name in Brevo. */
    public function getCode(): string;

    public function getSide(): TrackingEventSide;

    public function supports(object $subject): bool;

    /**
     * Null values are left out.
     *
     * @return array<string, mixed>
     */
    public function getProperties(object $subject, ChannelInterface $channel): array;
}
