<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

use Sylius\Component\Core\Model\ChannelInterface;

/** Which events a channel sends and under which name. */
interface TrackingEventSettingsInterface
{
    public function isEnabled(string $eventCode, ChannelInterface $channel): bool;

    public function getName(string $eventCode, ChannelInterface $channel): string;
}
