<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Messenger;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\RateLimitException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Retry\RetryStrategyInterface;

/**
 * 429 waits what Brevo asks, 5xx and network errors back off, other 4xx go straight to failed:
 * retrying a rejected payload or key gives the same answer.
 */
final class BrevoRetryStrategy implements RetryStrategyInterface
{
    private readonly MultiplierRetryStrategy $backoff;

    public function __construct(
        int $maxRetries = 3,
        int $delayMs = 10_000,
        float $multiplier = 3.0,
        int $maxDelayMs = 600_000,
    ) {
        $this->backoff = new MultiplierRetryStrategy($maxRetries, $delayMs, $multiplier, $maxDelayMs);
    }

    public function isRetryable(Envelope $message, ?\Throwable $throwable = null): bool
    {
        $brevoException = self::brevoException($throwable);
        if (null !== $brevoException && !$brevoException->isRetryable()) {
            return false;
        }

        return $this->backoff->isRetryable($message, $throwable);
    }

    public function getWaitingTime(Envelope $message, ?\Throwable $throwable = null): int
    {
        $brevoException = self::brevoException($throwable);
        if ($brevoException instanceof RateLimitException && null !== $brevoException->retryAfter) {
            return max(1, $brevoException->retryAfter) * 1000;
        }

        return $this->backoff->getWaitingTime($message, $throwable);
    }

    private static function brevoException(?\Throwable $throwable): ?BrevoException
    {
        $exceptions = $throwable instanceof HandlerFailedException ? $throwable->getWrappedExceptions() : [$throwable];

        foreach ($exceptions as $exception) {
            for ($current = $exception; null !== $current; $current = $current->getPrevious()) {
                if ($current instanceof BrevoException) {
                    return $current;
                }
            }
        }

        return null;
    }
}
