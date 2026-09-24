<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

/** Carries what's needed to find the contact: the customer is gone by the time it's handled. */
final class DeleteContact extends AbstractBrevoMessage
{
    public function __construct(
        string $channelCode,
        public readonly string $extId,
        public readonly ?string $email,
    ) {
        parent::__construct($channelCode, sprintf('contact:%s:delete', $extId));
    }
}
