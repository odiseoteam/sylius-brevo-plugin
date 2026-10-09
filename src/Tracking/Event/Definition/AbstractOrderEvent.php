<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition;

use Odiseo\SyliusBrevoPlugin\Tracking\Event\OrderEventProperties;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventSide;
use Sylius\Component\Core\Model\OrderInterface;

abstract class AbstractOrderEvent implements TrackingEventInterface
{
    public function __construct(protected readonly OrderEventProperties $properties)
    {
    }

    public function getSide(): TrackingEventSide
    {
        return TrackingEventSide::Server;
    }

    public function supports(object $subject): bool
    {
        return $subject instanceof OrderInterface;
    }
}
