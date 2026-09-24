<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

use Webmozart\Assert\Assert;

final class ContactIdentifier
{
    private function __construct(
        public readonly ContactIdentifierType $type,
        public readonly string $value,
    ) {
        Assert::stringNotEmpty($value);
    }

    public static function email(string $email): self
    {
        return new self(ContactIdentifierType::Email, $email);
    }

    public static function extId(string $extId): self
    {
        return new self(ContactIdentifierType::ExtId, $extId);
    }

    public static function contactId(int $id): self
    {
        return new self(ContactIdentifierType::ContactId, (string) $id);
    }

    /** Phone in E.164. */
    public static function phone(string $phone): self
    {
        return new self(ContactIdentifierType::Phone, $phone);
    }

    /** `/contacts/{identifier}` with the value encoded (emails may hold "+" or "/"). */
    public function path(string $suffix = ''): string
    {
        return '/contacts/' . rawurlencode($this->value) . $suffix;
    }

    /** @return array{identifierType: string} */
    public function query(): array
    {
        return ['identifierType' => $this->type->value];
    }
}
