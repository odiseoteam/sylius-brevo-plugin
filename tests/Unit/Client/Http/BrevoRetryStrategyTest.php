<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Http;

use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoRetryStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\RetryableHttpClient;

final class BrevoRetryStrategyTest extends TestCase
{
    public function testItRetriesATransientFailure(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 503]), new MockResponse('{}', ['http_code' => 200])]);

        self::assertSame(200, $client->request('POST', 'https://api.brevo.com/v3/contacts')->getStatusCode());
    }

    public function testItDoesNotRetryANonIdempotentCallOnAnAmbiguousFailure(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 500]), new MockResponse('{}', ['http_code' => 200])]);

        self::assertSame(500, $client->request('POST', 'https://api.brevo.com/v3/contacts')->getStatusCode());
    }

    public function testItRetriesAnIdempotentCallOnAnAmbiguousFailure(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 500]), new MockResponse('{}', ['http_code' => 200])]);

        self::assertSame(200, $client->request('GET', 'https://api.brevo.com/v3/account')->getStatusCode());
    }

    public function testItRetriesARateLimitWithAShortWait(): void
    {
        $client = $this->client([
            new MockResponse('', ['http_code' => 429, 'response_headers' => ['x-sib-ratelimit-reset' => '0']]),
            new MockResponse('{}', ['http_code' => 200]),
        ]);

        self::assertSame(200, $client->request('POST', 'https://api.brevo.com/v3/contacts')->getStatusCode());
    }

    public function testItGivesUpOnARateLimitWithALongWait(): void
    {
        $client = $this->client([
            new MockResponse('', ['http_code' => 429, 'response_headers' => ['x-sib-ratelimit-reset' => '60']]),
            new MockResponse('{}', ['http_code' => 200]),
        ]);

        self::assertSame(429, $client->request('POST', 'https://api.brevo.com/v3/contacts')->getStatusCode());
    }

    public function testItDoesNotRetryClientErrors(): void
    {
        $client = $this->client([new MockResponse('', ['http_code' => 400]), new MockResponse('{}', ['http_code' => 200])]);

        self::assertSame(400, $client->request('GET', 'https://api.brevo.com/v3/account')->getStatusCode());
    }

    /** @param list<MockResponse> $responses */
    private function client(array $responses): RetryableHttpClient
    {
        return new RetryableHttpClient(new MockHttpClient($responses), new BrevoRetryStrategy(0, 1.0, 5000), 2);
    }
}
