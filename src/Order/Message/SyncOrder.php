<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Order\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

final class SyncOrder extends AbstractBrevoMessage
{
    public function __construct(
        string $channelCode,
        public readonly int $orderId,
    ) {
        parent::__construct($channelCode, sprintf('order:%d', $orderId));
    }
}
