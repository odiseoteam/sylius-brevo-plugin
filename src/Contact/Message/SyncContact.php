<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Message;

use Odiseo\SyliusBrevoPlugin\Message\AbstractBrevoMessage;

final class SyncContact extends AbstractBrevoMessage
{
    public function __construct(
        string $channelCode,
        public readonly int $customerId,
        /** The customer just unsubscribed from the newsletter: leave the list too. */
        public readonly bool $leftNewsletter = false,
    ) {
        // Own key, so a later plain sync in the same request doesn't replace it.
        parent::__construct($channelCode, sprintf('contact:%d:sync%s', $customerId, $leftNewsletter ? ':left_newsletter' : ''));
    }
}
