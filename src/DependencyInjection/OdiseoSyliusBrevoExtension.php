<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\DependencyInjection;

use Odiseo\SyliusBrevoPlugin\Message\BrevoMessageInterface;
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
        /** @var array{
         *     api: array{key: ?string, base_url: string, timeout: float, max_retries: int},
         *     phone: array{default_region: ?string},
         *     contacts: array{attributes: array<string, string|false>},
         *     url: array{image_filter: string},
         * } $config
         */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('odiseo_brevo.api.key', $config['api']['key']);
        $container->setParameter('odiseo_brevo.api.base_url', $config['api']['base_url']);
        $container->setParameter('odiseo_brevo.api.timeout', $config['api']['timeout']);
        $container->setParameter('odiseo_brevo.api.max_retries', $config['api']['max_retries']);
        $container->setParameter('odiseo_brevo.phone.default_region', $config['phone']['default_region']);
        $container->setParameter('odiseo_brevo.url.image_filter', $config['url']['image_filter']);
        $container->setParameter('odiseo_brevo.contacts.attributes', $config['contacts']['attributes']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);

        $this->prependMessenger($container);

        if ($container->hasExtension('api_platform')) {
            $container->prependExtensionConfig('api_platform', ['mapping' => ['paths' => [\dirname(__DIR__, 2) . '/config/api_resources']]]);
        }

        if ($container->hasExtension('monolog')) {
            $container->prependExtensionConfig('monolog', ['channels' => ['brevo']]);
        }
    }

    /** Own bus and transport: sync by default, async with ODISEO_BREVO_MESSENGER_TRANSPORT_DSN. */
    private function prependMessenger(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('env(ODISEO_BREVO_MESSENGER_TRANSPORT_DSN)')) {
            $container->setParameter('env(ODISEO_BREVO_MESSENGER_TRANSPORT_DSN)', 'sync://');
        }
        if (!$container->hasParameter('env(ODISEO_BREVO_MESSENGER_FAILED_TRANSPORT_DSN)')) {
            $container->setParameter('env(ODISEO_BREVO_MESSENGER_FAILED_TRANSPORT_DSN)', 'doctrine://default?queue_name=odiseo_brevo_failed');
        }

        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'transports' => [
                    'odiseo_brevo' => [
                        'dsn' => '%env(ODISEO_BREVO_MESSENGER_TRANSPORT_DSN)%',
                        'failure_transport' => 'odiseo_brevo_failed',
                        'retry_strategy' => ['service' => 'odiseo_brevo.messenger.retry_strategy'],
                    ],
                    'odiseo_brevo_failed' => [
                        'dsn' => '%env(ODISEO_BREVO_MESSENGER_FAILED_TRANSPORT_DSN)%',
                    ],
                ],
                'buses' => [
                    'odiseo_brevo.bus' => [
                        'middleware' => ['odiseo_brevo.messenger.middleware.swallow_sync_failures'],
                    ],
                ],
                'routing' => [
                    BrevoMessageInterface::class => 'odiseo_brevo',
                ],
            ],
        ]);
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
