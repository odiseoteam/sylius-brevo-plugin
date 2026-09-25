<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

final class CustomerFilter
{
    public function __construct(
        public readonly bool $includeGuests = true,
        /** Created or updated since. */
        public readonly ?\DateTimeInterface $since = null,
        public readonly bool $onlySubscribed = false,
    ) {
    }
}
