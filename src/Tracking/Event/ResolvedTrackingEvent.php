<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

final readonly class ResolvedTrackingEvent
{
    /** @param array<string, mixed> $properties */
    public function __construct(
        public string $name,
        public array $properties,
    ) {
    }
}
