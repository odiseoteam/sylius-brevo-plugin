<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class OdiseoSyliusBrevoExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{api: array{base_url: string, timeout: float, max_retries: int}} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('odiseo_brevo.api.base_url', $config['api']['base_url']);
        $container->setParameter('odiseo_brevo.api.timeout', $config['api']['timeout']);
        $container->setParameter('odiseo_brevo.api.max_retries', $config['api']['max_retries']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);

        if ($container->hasExtension('monolog')) {
            $container->prependExtensionConfig('monolog', ['channels' => ['brevo']]);
        }
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Odiseo\SyliusBrevoPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@OdiseoSyliusBrevoPlugin/src/Migrations';
    }

    /** @return list<string> */
    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }
}
