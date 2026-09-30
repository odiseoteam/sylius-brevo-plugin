<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

/** An ecommerce product. Brevo keeps deleted ones, flagged with `isDeleted`. */
final readonly class ProductData
{
    /**
     * @param array<string, mixed> $fields other Brevo product fields (price, url, imageUrl, categories, metaInfo...)
     */
    public function __construct(
        public string $id,
        /** Required by Brevo to create the product, so it's sent even to delete. */
        public string $name,
        public array $fields = [],
        public bool $deleted = false,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, ...$this->fields, 'isDeleted' => $this->deleted];
    }
}
