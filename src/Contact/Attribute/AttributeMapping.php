<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Attribute;

use Sylius\Component\Core\Model\ChannelInterface;

/** Internal key => Brevo attribute name, the same for every channel. Unmapped keys go uppercase; false turns a key off. */
final class AttributeMapping implements AttributeMappingInterface
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

    public function brevoName(string $key, ?ChannelInterface $channel = null): ?string
    {
        $name = $this->mapping[$key] ?? strtoupper($key);

        return false === $name ? null : strtoupper($name);
    }
}
