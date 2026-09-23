<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Module;

final class ModuleRegistry implements ModuleRegistryInterface
{
    /** @var array<string, ModuleInterface> */
    private array $modules = [];

    /** @param iterable<ModuleInterface> $modules */
    public function __construct(iterable $modules)
    {
        foreach ($modules as $module) {
            if (isset($this->modules[$module->getCode()])) {
                throw new \LogicException(sprintf('Brevo module "%s" is registered twice.', $module->getCode()));
            }

            $this->modules[$module->getCode()] = $module;
        }
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function has(string $code): bool
    {
        return isset($this->modules[$code]);
    }
}
