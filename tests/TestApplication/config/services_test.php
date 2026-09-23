<?php

declare(strict_types=1);

use Odiseo\SyliusBrevoPlugin\Client\Api\AccountApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;

return function (ContainerConfigurator $container) {
    if (str_starts_with($container->env(), 'test')) {
        $container->import('../../../vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services.xml');
        $container->import('@OdiseoSyliusBrevoPlugin/tests/Behat/Resources/services.xml');

        $services = $container->services();

        // Tests never reach the real Brevo API.
        $services
            ->set('odiseo_brevo.client.http.transport', FakeBrevoHttpClient::class)
            ->public()
            ->tag('kernel.reset', ['method' => 'reset'])
        ;

        // Kept for integration tests, even before anything consumes them.
        $services->alias(BrevoHttpClientInterface::class, 'odiseo_brevo.client.http')->public();
        $services->alias(AccountApiInterface::class, 'odiseo_brevo.client.api.account')->public();
        $services->alias(ChannelUrlGeneratorInterface::class, 'odiseo_brevo.routing.channel_url_generator')->public();
    }
};
