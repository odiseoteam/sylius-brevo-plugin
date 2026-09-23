<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Double;

use Odiseo\SyliusBrevoPlugin\Module\ModuleInterface;

/** Registered in the test application only, until real modules exist. */
final class DummyModule implements ModuleInterface
{
    public function getCode(): string
    {
        return 'dummy';
    }

    public function getLabel(): string
    {
        return 'Dummy module';
    }
}
