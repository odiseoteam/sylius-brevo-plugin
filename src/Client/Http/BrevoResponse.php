<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Http;

final class BrevoResponse
{
    /**
     * @param array<array-key, mixed> $data Decoded JSON body, empty when there is none
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly array $data = [],
        public readonly array $headers = [],
    ) {
    }
}
