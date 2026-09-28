<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

/** Customer changes that came from Brevo are applied without sending them back. */
interface ContactSyncPauseInterface
{
    /**
     * Runs the callback (flushes included) without syncing the customers it changes.
     *
     * @template T
     *
     * @param \Closure(): T $callback
     *
     * @return T
     */
    public function pause(\Closure $callback): mixed;

    public function isPaused(): bool;
}
