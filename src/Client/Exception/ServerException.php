<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Exception;

/** Brevo failed on its side (5xx). */
final class ServerException extends BrevoException
{
    public function isRetryable(): bool
    {
        return true;
    }
}
