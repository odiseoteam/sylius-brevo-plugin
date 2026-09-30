<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

final class ActivateEcommerce extends AbstractBrevoMessage
{
    public function __construct(string $channelCode)
    {
        parent::__construct($channelCode, 'ecommerce:activate');
    }
}
