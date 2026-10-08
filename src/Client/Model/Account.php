<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

final class Account
{
    /** @param list<Plan> $plans */
    public function __construct(
        public readonly string $email,
        public readonly ?string $companyName = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly array $plans = [],
        /** The tracker's client key, when marketing automation is on. */
        public readonly ?string $trackerClientKey = null,
    ) {
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        $plans = [];
        foreach (is_array($data['plan'] ?? null) ? $data['plan'] : [] as $plan) {
            if (is_array($plan)) {
                $plans[] = Plan::fromArray($plan);
            }
        }

        $automation = is_array($data['marketingAutomation'] ?? null) ? $data['marketingAutomation'] : [];

        return new self(
            is_string($data['email'] ?? null) ? $data['email'] : '',
            self::stringOrNull($data['companyName'] ?? null),
            self::stringOrNull($data['firstName'] ?? null),
            self::stringOrNull($data['lastName'] ?? null),
            $plans,
            true === ($automation['enabled'] ?? null) ? self::stringOrNull($automation['key'] ?? null) : null,
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && '' !== $value ? $value : null;
    }
}
