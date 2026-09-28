<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

final class ContactSyncPause implements ContactSyncPauseInterface
{
    private int $depth = 0;

    public function pause(\Closure $callback): mixed
    {
        ++$this->depth;

        try {
            return $callback();
        } finally {
            --$this->depth;
        }
    }

    public function isPaused(): bool
    {
        return $this->depth > 0;
    }
}
