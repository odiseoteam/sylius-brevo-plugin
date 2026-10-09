<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

enum TrackingEventSide: string
{
    /** Pushed by the shop's tracker, only with the visitor's consent. */
    case Browser = 'browser';

    /** Sent by the Events API once the visitor has an email. */
    case Server = 'server';
}
