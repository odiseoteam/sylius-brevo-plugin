<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Integration\DependencyInjection;

use Doctrine\Migrations\DependencyFactory;
use Odiseo\SyliusBrevoPlugin\OdiseoSyliusBrevoPlugin;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ContainerTest extends KernelTestCase
{
    public function testThePluginIsRegisteredInTheKernel(): void
    {
        $kernel = self::bootKernel();

        self::assertInstanceOf(OdiseoSyliusBrevoPlugin::class, $kernel->getBundle('OdiseoSyliusBrevoPlugin'));
    }

    public function testItsMigrationsAreRegistered(): void
    {
        self::bootKernel();

        $dependencyFactory = self::getContainer()->get('doctrine.migrations.dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $dependencyFactory);

        self::assertArrayHasKey(
            'Odiseo\SyliusBrevoPlugin\Migrations',
            $dependencyFactory->getConfiguration()->getMigrationDirectories(),
        );
    }
}
