<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

/**
 * What to write on a contact. Null means "leave as is"; attribute names are sent uppercase,
 * as Brevo expects.
 */
final class ContactData
{
    /** @var array<string, scalar|list<string>|null> */
    public readonly array $attributes;

    /**
     * @param array<string, scalar|list<string>|null> $attributes
     * @param list<int> $listIds lists to add the contact to
     * @param list<int> $unlinkListIds lists to remove the contact from (updates only)
     */
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?string $extId = null,
        array $attributes = [],
        public readonly array $listIds = [],
        public readonly array $unlinkListIds = [],
        public readonly ?bool $emailBlacklisted = null,
        public readonly ?bool $smsBlacklisted = null,
    ) {
        $this->attributes = array_change_key_case($attributes, \CASE_UPPER);
    }

    /** @return array<string, mixed> */
    public function toCreatePayload(): array
    {
        return array_filter([
            'email' => $this->email,
            'ext_id' => $this->extId,
            'attributes' => $this->attributes,
            'listIds' => $this->listIds,
            'emailBlacklisted' => $this->emailBlacklisted,
            'smsBlacklisted' => $this->smsBlacklisted,
        ], self::isSet(...));
    }

    /**
     * The email can't be set directly on update: it goes as the EMAIL attribute.
     *
     * @return array<string, mixed>
     */
    public function toUpdatePayload(): array
    {
        $attributes = $this->attributes;
        if (null !== $this->email) {
            $attributes['EMAIL'] = $this->email;
        }

        return array_filter([
            'ext_id' => $this->extId,
            'attributes' => $attributes,
            'listIds' => $this->listIds,
            'unlinkListIds' => $this->unlinkListIds,
            'emailBlacklisted' => $this->emailBlacklisted,
            'smsBlacklisted' => $this->smsBlacklisted,
        ], self::isSet(...));
    }

    private static function isSet(mixed $value): bool
    {
        return null !== $value && [] !== $value;
    }
}
