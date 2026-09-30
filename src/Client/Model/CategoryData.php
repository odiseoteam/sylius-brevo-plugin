<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

/** An ecommerce category. Brevo can't delete them: a deleted one is sent with `isDeleted`. */
final readonly class CategoryData
{
    public function __construct(
        public string $id,
        /** Required by Brevo even to delete; without it the call fails with a misleading 403. */
        public string $name,
        public ?string $url = null,
        public bool $deleted = false,
    ) {
    }

    /** @return array{id: string, name: string, url?: string, isDeleted: bool} */
    public function toArray(): array
    {
        $data = ['id' => $this->id, 'name' => $this->name];
        if (null !== $this->url) {
            $data['url'] = $this->url;
        }
        $data['isDeleted'] = $this->deleted;

        return $data;
    }
}
