<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Exception;

/** Too many requests (429). */
final class RateLimitException extends BrevoException
{
    public function __construct(
        string $message,
        ?string $errorCode = null,
        /** Seconds until the limit resets, when Brevo tells. */
        public readonly ?int $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 429, $errorCode, $previous);
    }

    public function isRetryable(): bool
    {
        return true;
    }
}
