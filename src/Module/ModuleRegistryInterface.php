<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Module;

interface ModuleRegistryInterface
{
    /** @return array<string, ModuleInterface> indexed by code */
    public function all(): array;

    public function has(string $code): bool;
}
