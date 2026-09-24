<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

final class CustomerOrderStats
{
    public function __construct(
        public readonly int $count = 0,
        public readonly int $total = 0,
        public readonly ?\DateTimeInterface $firstOrderAt = null,
        public readonly ?\DateTimeInterface $lastOrderAt = null,
        public readonly ?string $lastLocaleCode = null,
    ) {
    }

    public function averageTotal(): int
    {
        return 0 === $this->count ? 0 : intdiv($this->total, $this->count);
    }
}
