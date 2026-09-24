<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Attribute;

/** Internal key => Brevo attribute name. Unmapped keys go uppercase; false turns a key off. */
final class AttributeMapping
{
    /** Where Brevo's own attribute names differ from the uppercased key. */
    public const DEFAULTS = [
        'first_name' => 'FIRSTNAME',
        'last_name' => 'LASTNAME',
        'phone' => 'SMS',
        'postcode' => 'ZIP_CODE',
    ];

    /** @var array<string, string|false> */
    private readonly array $mapping;

    /** @param array<string, string|false> $overrides */
    public function __construct(array $overrides = [])
    {
        $this->mapping = [...self::DEFAULTS, ...$overrides];
    }

    public function brevoName(string $key): ?string
    {
        $name = $this->mapping[$key] ?? strtoupper($key);

        return false === $name ? null : strtoupper($name);
    }
}
