<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Http;

use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Short in-process retry. Longer waits are left to Messenger.
 *
 * Non-idempotent calls are only retried when Brevo surely did not process them (429, 502, 503).
 */
final class BrevoRetryStrategy implements RetryStrategyInterface
{
    public const STATUS_CODES = [
        0 => GenericRetryStrategy::IDEMPOTENT_METHODS,
        429,
        500 => GenericRetryStrategy::IDEMPOTENT_METHODS,
        502,
        503,
        504 => GenericRetryStrategy::IDEMPOTENT_METHODS,
    ];

    private readonly GenericRetryStrategy $inner;

    public function __construct(
        int $delayMs = 500,
        float $multiplier = 2.0,
        private readonly int $maxDelayMs = 5000,
    ) {
        $this->inner = new GenericRetryStrategy(self::STATUS_CODES, $delayMs, $multiplier, $maxDelayMs);
    }

    public function shouldRetry(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $exception): ?bool
    {
        if (null === $exception && 429 === $context->getStatusCode()) {
            $wait = RateLimitHeaders::secondsUntilReset($context->getHeaders());

            // A long wait would block the worker: fail and let the caller reschedule.
            if (null !== $wait && $wait * 1000 > $this->maxDelayMs) {
                return false;
            }
        }

        return $this->inner->shouldRetry($context, $responseContent, $exception);
    }

    public function getDelay(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $exception): int
    {
        if (null === $exception && 429 === $context->getStatusCode()) {
            $wait = RateLimitHeaders::secondsUntilReset($context->getHeaders());

            if (null !== $wait) {
                return $wait * 1000;
            }
        }

        return $this->inner->getDelay($context, $responseContent, $exception);
    }
}
