<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

final class Plan
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $creditsType = null,
        public readonly ?float $credits = null,
    ) {
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            is_string($data['type'] ?? null) ? $data['type'] : 'unknown',
            is_string($data['creditsType'] ?? null) ? $data['creditsType'] : null,
            is_numeric($data['credits'] ?? null) ? (float) $data['credits'] : null,
        );
    }
}
