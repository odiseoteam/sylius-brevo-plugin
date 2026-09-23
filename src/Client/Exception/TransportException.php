<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Exception;

/** No response: network error or timeout. */
final class TransportException extends BrevoException
{
    public function __construct(string $message, int $statusCode = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, null, $previous);
    }

    public function isRetryable(): bool
    {
        return true;
    }
}
