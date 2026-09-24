<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

/** Outcome of adding or removing contacts from a list, merged across batches. */
final class ListMembershipResult
{
    /**
     * @param list<string> $success identifiers Brevo applied
     * @param list<string> $failure identifiers Brevo rejected (e.g. unknown contacts)
     */
    public function __construct(
        public readonly array $success = [],
        public readonly array $failure = [],
    ) {
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        $identifiers = static fn (string $key): array => array_values(array_map(
            'strval',
            array_filter(ArrayReader::array($data, $key), 'is_scalar'),
        ));

        return new self($identifiers('success'), $identifiers('failure'));
    }

    public function merge(self $other): self
    {
        return new self([...$this->success, ...$other->success], [...$this->failure, ...$other->failure]);
    }
}
