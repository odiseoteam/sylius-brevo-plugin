<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Http;

use Webmozart\Assert\Assert;

/** Brevo account credentials used for a single call. */
final class Credentials
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $apiKey,
    ) {
        Assert::stringNotEmpty($apiKey, 'The Brevo API key cannot be empty.');
    }

    /** Keeps the key out of dumps and exception traces. */
    public function __debugInfo(): array
    {
        return ['apiKey' => '***'];
    }
}
