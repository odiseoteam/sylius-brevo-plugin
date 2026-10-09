<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\EventData;

interface EventsApiInterface
{
    /**
     * Brevo creates the contact when no identifier matches one.
     *
     * @throws BrevoException
     */
    public function track(Credentials $credentials, EventData $event): void;

    /**
     * Queued by Brevo; invalid events are dropped without failing the call.
     *
     * @param list<EventData> $events
     *
     * @throws BrevoException
     */
    public function trackBatch(Credentials $credentials, array $events): void;
}
