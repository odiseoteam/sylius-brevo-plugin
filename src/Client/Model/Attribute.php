<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

/** A contact attribute. Category and type stay strings: Brevo adds values over time. */
final class Attribute
{
    public const CATEGORY_NORMAL = 'normal';

    public const CATEGORY_TRANSACTIONAL = 'transactional';

    public const CATEGORY_CATEGORY = 'category';

    public const CATEGORY_CALCULATED = 'calculated';

    public const CATEGORY_GLOBAL = 'global';

    public const TYPE_TEXT = 'text';

    public const TYPE_DATE = 'date';

    public const TYPE_FLOAT = 'float';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_ID = 'id';

    public const TYPE_MULTIPLE_CHOICE = 'multiple-choice';

    /**
     * @param list<array{value: int, label: string}> $enumeration options of "category" attributes
     * @param list<string> $multiCategoryOptions options of "multiple-choice" attributes
     */
    public function __construct(
        public readonly string $name,
        public readonly string $category,
        public readonly ?string $type = null,
        public readonly array $enumeration = [],
        public readonly array $multiCategoryOptions = [],
        public readonly ?string $calculatedValue = null,
    ) {
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        $enumeration = [];
        foreach (ArrayReader::arrays($data, 'enumeration') as $option) {
            $value = ArrayReader::int($option, 'value');
            if (null !== $value) {
                $enumeration[] = ['value' => $value, 'label' => ArrayReader::string($option, 'label') ?? (string) $value];
            }
        }

        return new self(
            ArrayReader::string($data, 'name') ?? '',
            ArrayReader::string($data, 'category') ?? self::CATEGORY_NORMAL,
            ArrayReader::string($data, 'type'),
            $enumeration,
            ArrayReader::strings($data, 'multiCategoryOptions'),
            ArrayReader::string($data, 'calculatedValue'),
        );
    }

    /** Only these can be set on a contact. */
    public function isWritable(): bool
    {
        return in_array($this->category, [self::CATEGORY_NORMAL, self::CATEGORY_CATEGORY], true);
    }
}
