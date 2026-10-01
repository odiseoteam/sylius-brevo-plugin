<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

/** An ecommerce order. Brevo replaces it whole on each send. */
final readonly class OrderData
{
    /**
     * @param list<array{productId: string, quantity: int, price: float, variantId?: string}> $products
     * @param array<string, mixed> $fields other Brevo order fields (identifiers, billing, coupons, metaInfo...)
     */
    public function __construct(
        public string $id,
        public string $status,
        public float $amount,
        public \DateTimeInterface $createdAt,
        public \DateTimeInterface $updatedAt,
        public array $products,
        public array $fields = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'amount' => $this->amount,
            'createdAt' => self::date($this->createdAt),
            'updatedAt' => self::date($this->updatedAt),
            'products' => $this->products,
            ...$this->fields,
        ];
    }

    private static function date(\DateTimeInterface $date): string
    {
        return \DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
