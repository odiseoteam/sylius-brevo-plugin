<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Http;

use Odiseo\SyliusBrevoPlugin\Client\Exception\ValidationException;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Http\LoggingBrevoHttpClient;
use Odiseo\SyliusBrevoPlugin\Logging\SensitiveDataMasker;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use PHPUnit\Framework\TestCase;
use Tests\Odiseo\SyliusBrevoPlugin\Double\InMemoryLogger;

final class LoggingBrevoHttpClientTest extends TestCase
{
    private FakeBrevoHttpClient $inner;

    private InMemoryLogger $logger;

    protected function setUp(): void
    {
        $this->inner = new FakeBrevoHttpClient();
        $this->logger = new InMemoryLogger();
    }

    public function testItLogsSuccessfulCallsWithoutSecrets(): void
    {
        $this->inner->queue('GET', '/contacts/john@example.com', new BrevoResponse(200));

        $this->client()->request(new Credentials('xkeysib-secret'), 'GET', '/contacts/john@example.com');

        self::assertCount(1, $this->logger->records);
        self::assertSame('info', $this->logger->records[0]['level']);
        self::assertSame('/contacts/j***@example.com', $this->logger->records[0]['context']['path']);
        self::assertSame(200, $this->logger->records[0]['context']['status']);
        self::assertStringNotContainsString('xkeysib-secret', serialize($this->logger->records));
    }

    public function testItLogsAndRethrowsFailures(): void
    {
        $exception = new ValidationException('Invalid email john@example.com', 400, 'invalid_parameter');
        $this->inner->queue('POST', '/contacts', $exception);

        try {
            $this->client()->request(new Credentials('xkeysib-secret'), 'POST', '/contacts', [], ['email' => 'john@example.com']);
            self::fail('The exception should be rethrown.');
        } catch (ValidationException $caught) {
            self::assertSame($exception, $caught);
        }

        self::assertSame('error', $this->logger->records[0]['level']);
        self::assertSame('invalid_parameter', $this->logger->records[0]['context']['error_code']);
        self::assertStringNotContainsString('john@example.com', serialize($this->logger->records));
    }

    private function client(): LoggingBrevoHttpClient
    {
        return new LoggingBrevoHttpClient($this->inner, $this->logger, new SensitiveDataMasker());
    }
}
