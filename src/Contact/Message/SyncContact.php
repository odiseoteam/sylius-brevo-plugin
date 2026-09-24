<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

final class SyncContact extends AbstractBrevoMessage
{
    public function __construct(
        string $channelCode,
        public readonly int $customerId,
    ) {
        parent::__construct($channelCode, sprintf('contact:%d:sync', $customerId));
    }
}
