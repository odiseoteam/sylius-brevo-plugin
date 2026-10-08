<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

final class FetchTrackerClientKey extends AbstractBrevoMessage
{
    public function __construct(string $channelCode)
    {
        parent::__construct($channelCode, 'tracking:client_key');
    }
}
