<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

final class ContactList
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?int $folderId = null,
        public readonly int $totalSubscribers = 0,
        public readonly int $uniqueSubscribers = 0,
        public readonly int $totalBlacklisted = 0,
    ) {
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            ArrayReader::int($data, 'id') ?? 0,
            ArrayReader::string($data, 'name') ?? '',
            ArrayReader::int($data, 'folderId'),
            ArrayReader::int($data, 'totalSubscribers') ?? 0,
            ArrayReader::int($data, 'uniqueSubscribers') ?? 0,
            ArrayReader::int($data, 'totalBlacklisted') ?? 0,
        );
    }
}
