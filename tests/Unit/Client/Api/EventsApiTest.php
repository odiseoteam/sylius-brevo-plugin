<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Api\EventsApi;
use Odiseo\SyliusBrevoPlugin\Client\Exception\ValidationException;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\EventData;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use PHPUnit\Framework\TestCase;
use Tests\Odiseo\SyliusBrevoPlugin\Double\BrevoFixture;

final class EventsApiTest extends TestCase
{
    private FakeBrevoHttpClient $client;

    private EventsApi $api;

    protected function setUp(): void
    {
        $this->client = new FakeBrevoHttpClient();
        $this->api = new EventsApi($this->client);
    }

    public function testItSendsAnEventLeavingOutWhatIsEmpty(): void
    {
        $this->client->queue('POST', '/events', new BrevoResponse(204));

        $this->api->track(new Credentials('key'), new EventData(
            'cart_updated',
            ['email_id' => 'vimes@example.com', 'ext_id' => '7'],
            ['total' => 19.99, 'items' => [['name' => 'PHP T-Shirt', 'quantity' => 1]]],
            date: new \DateTimeImmutable('2026-10-08 10:00:00+02:00'),
        ));

        self::assertSame([
            'event_name' => 'cart_updated',
            'identifiers' => ['email_id' => 'vimes@example.com', 'ext_id' => '7'],
            'event_properties' => ['total' => 19.99, 'items' => [['name' => 'PHP T-Shirt', 'quantity' => 1]]],
            'event_date' => '2026-10-08T10:00:00+02:00',
        ], $this->client->lastRequest()?->json);
    }

    public function testItSendsBatchesOf100(): void
    {
        $this->client->queue('POST', '/events/batch', BrevoFixture::response('events_batch', 202));

        $events = array_map(static fn (int $i): EventData => new EventData('product_viewed', ['email_id' => $i . '@example.com']), range(1, 101));
        $this->api->trackBatch(new Credentials('key'), $events);

        $requests = $this->client->requests('POST', '/events/batch');
        self::assertCount(2, $requests);
        self::assertIsArray($requests[0]->json['events'] ?? null);
        self::assertCount(100, $requests[0]->json['events']);
        self::assertSame([['event_name' => 'product_viewed', 'identifiers' => ['email_id' => '101@example.com']]], $requests[1]->json['events'] ?? null);
    }

    public function testInvalidEventsFail(): void
    {
        $this->client->queue('POST', '/events', new BrevoResponse(400, ['code' => 'invalid_parameter', 'message' => 'event_name is invalid']));

        $this->expectException(ValidationException::class);
        $this->api->track(new Credentials('key'), new EventData('cart updated', ['email_id' => 'vimes@example.com']));
    }
}
