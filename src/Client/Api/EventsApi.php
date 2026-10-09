<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\EventData;

final class EventsApi implements EventsApiInterface
{
    /** Events per batch call (Brevo documents no limit). */
    public const BATCH_SIZE = 100;

    public function __construct(private readonly BrevoHttpClientInterface $client)
    {
    }

    public function track(Credentials $credentials, EventData $event): void
    {
        $this->client->request($credentials, 'POST', '/events', json: $event->toArray());
    }

    public function trackBatch(Credentials $credentials, array $events): void
    {
        foreach (array_chunk($events, self::BATCH_SIZE) as $batch) {
            $this->client->request($credentials, 'POST', '/events/batch', json: [
                'events' => array_map(static fn (EventData $event): array => $event->toArray(), $batch),
            ]);
        }
    }
}
