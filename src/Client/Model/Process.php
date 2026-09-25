<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

/** A Brevo background process, e.g. a contact import. */
final class Process
{
    public const STATUS_COMPLETED = 'completed';

    private const FINISHED = [self::STATUS_COMPLETED, 'failed', 'cancelled'];

    /** @param array<string, mixed> $importInfo duplicates and invalid emails of an import */
    public function __construct(
        public readonly int $id,
        public readonly string $status,
        public readonly ?string $name = null,
        public readonly array $importInfo = [],
    ) {
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $importInfo */
        $importInfo = array_filter(ArrayReader::array(ArrayReader::array($data, 'info'), 'import'), static fn (mixed $value): bool => null !== $value && '' !== $value);

        return new self(
            ArrayReader::int($data, 'id') ?? 0,
            ArrayReader::string($data, 'status') ?? 'queued',
            ArrayReader::string($data, 'name'),
            $importInfo,
        );
    }

    public function isFinished(): bool
    {
        return in_array($this->status, self::FINISHED, true);
    }
}
