<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition\OrderCompleted;

final class TrackOrderEvent extends AbstractBrevoMessage
{
    public function __construct(
        string $channelCode,
        public readonly int $orderId,
        public readonly string $eventCode,
    ) {
        // One cart event per cart and request, the last one wins.
        parent::__construct($channelCode, sprintf('%s:%d', OrderCompleted::CODE === $eventCode ? $eventCode : 'cart', $orderId));
    }
}
