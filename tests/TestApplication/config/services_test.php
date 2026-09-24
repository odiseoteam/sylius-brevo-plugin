<?php

declare(strict_types=1);

use Odiseo\SyliusBrevoPlugin\Client\Api\AccountApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Api\AttributesApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Api\ContactsApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Api\ListsApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Module\ModuleCheckerInterface;
use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ReferenceConfigurator;
use Tests\Odiseo\SyliusBrevoPlugin\Double\DummyModule;
use Tests\Odiseo\SyliusBrevoPlugin\Double\DummyOrderPlacedHandler;
use Tests\Odiseo\SyliusBrevoPlugin\Double\DummyOrderPlacedListener;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;

return function (ContainerConfigurator $container) {
    if (str_starts_with($container->env(), 'test')) {
        $container->import('../../../vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services.xml');
        $container->import('@OdiseoSyliusBrevoPlugin/tests/Behat/Resources/services.xml');

        $services = $container->services();

        // Tests never reach the real Brevo API.
        $services
            ->set('odiseo_brevo.client.http.transport', FakeBrevoHttpClient::class)
            ->args(['%kernel.cache_dir%/odiseo_brevo_fake_client.data'])
            ->public()
        ;

        $services->set('odiseo_brevo.test.module.dummy', DummyModule::class)->tag('odiseo_brevo.module');

        // A fake module flow: a placed order goes to Brevo through the bus.
        $services
            ->set('odiseo_brevo.test.listener.dummy_order_placed', DummyOrderPlacedListener::class)
            ->args([new ReferenceConfigurator('odiseo_brevo.messenger.dispatcher')])
            ->tag('kernel.event_listener', ['event' => 'sylius.order.post_complete'])
        ;
        $services
            ->set('odiseo_brevo.test.handler.dummy_order_placed', DummyOrderPlacedHandler::class)
            ->args([new ReferenceConfigurator('sylius.repository.channel'), new ReferenceConfigurator('odiseo_brevo.provider.configuration'), new ReferenceConfigurator('odiseo_brevo.client.http')])
            ->tag('messenger.message_handler', ['bus' => 'odiseo_brevo.bus'])
        ;

        // Kept for integration tests, even before anything consumes them.
        $services->alias(BrevoHttpClientInterface::class, 'odiseo_brevo.client.http')->public();
        $services->alias(AccountApiInterface::class, 'odiseo_brevo.client.api.account')->public();
        $services->alias(ChannelUrlGeneratorInterface::class, 'odiseo_brevo.routing.channel_url_generator')->public();
        $services->alias(ConfigurationProviderInterface::class, 'odiseo_brevo.provider.configuration')->public();
        $services->alias(ModuleCheckerInterface::class, 'odiseo_brevo.checker.module')->public();
        $services->alias(ContactsApiInterface::class, 'odiseo_brevo.client.api.contacts')->public();
        $services->alias(AttributesApiInterface::class, 'odiseo_brevo.client.api.attributes')->public();
        $services->alias(ListsApiInterface::class, 'odiseo_brevo.client.api.lists')->public();
    }
};
