<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

final readonly class BatchResult
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
    ) {
    }

    public function add(self $other): self
    {
        return new self($this->created + $other->created, $this->updated + $other->updated);
    }
}
