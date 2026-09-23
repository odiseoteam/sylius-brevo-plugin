<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Http;

use Odiseo\SyliusBrevoPlugin\Client\Exception\AuthenticationException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\TransportException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\ValidationException;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClient;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BrevoHttpClientTest extends TestCase
{
    public function testItSendsAuthenticatedJsonRequests(): void
    {
        $mockResponse = new MockResponse('{"id": 42}', ['http_code' => 201]);
        $client = new BrevoHttpClient(new MockHttpClient($mockResponse), 'https://api.brevo.com/v3/', 5.0);

        $response = $client->request(new Credentials('xkeysib-secret'), 'POST', '/contacts', ['foo' => 'bar'], ['email' => 'john@example.com']);

        self::assertSame('POST', $mockResponse->getRequestMethod());
        self::assertSame('https://api.brevo.com/v3/contacts?foo=bar', $mockResponse->getRequestUrl());
        $headers = $mockResponse->getRequestOptions()['headers'];
        self::assertIsArray($headers);
        self::assertContains('api-key: xkeysib-secret', $headers);
        self::assertContains('accept: application/json', $headers);
        self::assertSame('{"email":"john@example.com"}', $mockResponse->getRequestOptions()['body']);
        self::assertSame(201, $response->statusCode);
        self::assertSame(['id' => 42], $response->data);
    }

    public function testItReturnsAnEmptyPayloadForNoContent(): void
    {
        $client = new BrevoHttpClient(new MockHttpClient(new MockResponse('', ['http_code' => 204])), 'https://api.brevo.com/v3', 5.0);

        $response = $client->request(new Credentials('key'), 'PUT', '/contacts/1');

        self::assertSame(204, $response->statusCode);
        self::assertSame([], $response->data);
    }

    public function testItThrowsTheMappedExceptionOnErrors(): void
    {
        $client = new BrevoHttpClient(
            new MockHttpClient(new MockResponse('{"code":"unauthorized","message":"Key not found"}', ['http_code' => 401])),
            'https://api.brevo.com/v3',
            5.0,
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Brevo API error 401 (unauthorized): Key not found');

        $client->request(new Credentials('key'), 'GET', '/account');
    }

    public function testItKeepsNonJsonErrorBodiesAsTheMessage(): void
    {
        $client = new BrevoHttpClient(
            new MockHttpClient(new MockResponse('Bad request', ['http_code' => 400])),
            'https://api.brevo.com/v3',
            5.0,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Bad request');

        $client->request(new Credentials('key'), 'GET', '/account');
    }

    public function testItWrapsTransportFailures(): void
    {
        $client = new BrevoHttpClient(
            new MockHttpClient(new MockResponse('', ['error' => 'Connection refused'])),
            'https://api.brevo.com/v3',
            5.0,
        );

        $this->expectException(TransportException::class);

        $client->request(new Credentials('key'), 'GET', '/account');
    }
}
