<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Messenger;

use Odiseo\SyliusBrevoPlugin\Client\Exception\AuthenticationException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\RateLimitException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\ServerException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\ValidationException;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoRetryStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\RedeliveryStamp;
use Tests\Odiseo\SyliusBrevoPlugin\Double\DummyOrderPlaced;

final class BrevoRetryStrategyTest extends TestCase
{
    private BrevoRetryStrategy $strategy;

    private Envelope $envelope;

    protected function setUp(): void
    {
        $this->strategy = new BrevoRetryStrategy(maxRetries: 3, delayMs: 1000, multiplier: 3.0, maxDelayMs: 60_000);
        $this->envelope = new Envelope(new DummyOrderPlaced('WEB', '1'));
    }

    public function testServerErrorsBackOff(): void
    {
        $failure = $this->failure(new ServerException('down', 503));

        self::assertTrue($this->strategy->isRetryable($this->envelope, $failure));
        // Symfony 7 adds up to 10% jitter.
        self::assertEqualsWithDelta(1000, $this->strategy->getWaitingTime($this->envelope, $failure), 100);
        self::assertEqualsWithDelta(9000, $this->strategy->getWaitingTime($this->envelope->with(new RedeliveryStamp(2)), $failure), 900);
        self::assertFalse($this->strategy->isRetryable($this->envelope->with(new RedeliveryStamp(3)), $failure));
    }

    public function testRateLimitsWaitWhatBrevoAsks(): void
    {
        $failure = $this->failure(new RateLimitException('slow down', retryAfter: 42));

        self::assertTrue($this->strategy->isRetryable($this->envelope, $failure));
        self::assertSame(42_000, $this->strategy->getWaitingTime($this->envelope, $failure));
    }

    public function testRejectedRequestsAreNotRetried(): void
    {
        self::assertFalse($this->strategy->isRetryable($this->envelope, $this->failure(new ValidationException('bad payload', 400))));
        self::assertFalse($this->strategy->isRetryable($this->envelope, $this->failure(new AuthenticationException('bad key', 401))));
    }

    public function testOtherFailuresBackOff(): void
    {
        self::assertTrue($this->strategy->isRetryable($this->envelope, $this->failure(new \RuntimeException('db gone'))));
        self::assertTrue($this->strategy->isRetryable($this->envelope, null));
    }

    private function failure(\Throwable $exception): HandlerFailedException
    {
        return new HandlerFailedException($this->envelope, [$exception]);
    }
}
