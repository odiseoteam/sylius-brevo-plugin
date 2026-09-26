<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Module;

/** A module that only works along with others; a configuration can't enable it alone. */
interface DependentModuleInterface extends ModuleInterface
{
    /** @return list<string> module codes */
    public function getRequiredModules(): array;
}
