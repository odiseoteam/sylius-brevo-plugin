<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Double;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

final class DummyOrderPlaced extends AbstractBrevoMessage
{
    public function __construct(
        string $channelCode,
        public readonly string $orderNumber,
    ) {
        parent::__construct($channelCode, sprintf('order:%s:placed', $orderNumber));
    }
}
