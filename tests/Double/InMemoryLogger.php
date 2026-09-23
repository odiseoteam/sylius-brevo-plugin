<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Double;

use Psr\Log\AbstractLogger;

final class InMemoryLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<array-key, mixed>}> */
    public array $records = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
    }
}
