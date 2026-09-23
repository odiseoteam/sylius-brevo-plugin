<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Exception;

/** Base for every failure talking to Brevo. */
class BrevoException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?string $errorCode = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /** Whether trying again later may succeed. */
    public function isRetryable(): bool
    {
        return false;
    }
}
