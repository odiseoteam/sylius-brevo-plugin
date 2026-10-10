<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition\CartDeleted;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition\CartUpdated;

final class TrackOrderEvent extends AbstractBrevoMessage
{
    public function __construct(
        string $channelCode,
        public readonly int $orderId,
        public readonly string $eventCode,
    ) {
        // One cart event per cart and request, the last one wins.
        parent::__construct($channelCode, sprintf('%s:%d', self::isCartEvent($eventCode) ? 'cart' : $eventCode, $orderId));
    }

    /** Cart events are for carts only; any other code is for placed orders. */
    public static function isCartEvent(string $eventCode): bool
    {
        return in_array($eventCode, [CartUpdated::CODE, CartDeleted::CODE], true);
    }
}
