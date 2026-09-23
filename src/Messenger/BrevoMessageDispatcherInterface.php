<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Messenger;

use Odiseo\SyliusBrevoPlugin\Message\BrevoMessageInterface;

interface BrevoMessageDispatcherInterface
{
    /** Never throws: a Brevo message must not break the caller. */
    public function dispatch(BrevoMessageInterface $message): void;
}
