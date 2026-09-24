<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

final class Contact
{
    /**
     * @param array<string, mixed> $attributes
     * @param list<int> $listIds
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $email,
        public readonly ?string $extId,
        public readonly array $attributes = [],
        public readonly array $listIds = [],
        public readonly bool $emailBlacklisted = false,
        public readonly bool $smsBlacklisted = false,
        public readonly ?\DateTimeImmutable $createdAt = null,
        public readonly ?\DateTimeImmutable $modifiedAt = null,
    ) {
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $attributes */
        $attributes = array_filter(ArrayReader::array($data, 'attributes'), 'is_string', \ARRAY_FILTER_USE_KEY);

        return new self(
            ArrayReader::int($data, 'id') ?? 0,
            ArrayReader::string($data, 'email'),
            ArrayReader::string($data, 'ext_id') ?? ArrayReader::string($attributes, 'EXT_ID'),
            $attributes,
            ArrayReader::ints($data, 'listIds'),
            ArrayReader::bool($data, 'emailBlacklisted'),
            ArrayReader::bool($data, 'smsBlacklisted'),
            ArrayReader::dateTime($data, 'createdAt'),
            ArrayReader::dateTime($data, 'modifiedAt'),
        );
    }

    public function isInList(int $listId): bool
    {
        return in_array($listId, $this->listIds, true);
    }
}
