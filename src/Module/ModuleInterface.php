<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Module;

/** A feature that can be switched on per channel. Register it with the `odiseo_brevo.module` tag. */
interface ModuleInterface
{
    public function getCode(): string;

    /** Translation key. */
    public function getLabel(): string;
}
