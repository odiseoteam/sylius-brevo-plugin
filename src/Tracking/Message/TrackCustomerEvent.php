<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

final class TrackCustomerEvent extends AbstractBrevoMessage
{
    public function __construct(
        string $channelCode,
        public readonly int $customerId,
        public readonly string $eventCode,
    ) {
        parent::__construct($channelCode, sprintf('%s:%d', $eventCode, $customerId));
    }
}
