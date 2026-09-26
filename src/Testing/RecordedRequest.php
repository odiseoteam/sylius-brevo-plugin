<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Testing;

final class RecordedRequest
{
    /**
     * @param array<string, mixed> $query
     * @param array<array-key, mixed>|null $json
     */
    public function __construct(
        public readonly string $apiKey,
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly ?array $json,
    ) {
    }
}
