<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit;

use Odiseo\SyliusBrevoPlugin\OdiseoSyliusBrevoPlugin;
use PHPUnit\Framework\TestCase;

final class OdiseoSyliusBrevoPluginTest extends TestCase
{
    public function testItPointsToThePluginRoot(): void
    {
        $plugin = new OdiseoSyliusBrevoPlugin();

        self::assertFileExists($plugin->getPath() . '/composer.json');
        self::assertDirectoryExists($plugin->getPath() . '/config');
    }
}
