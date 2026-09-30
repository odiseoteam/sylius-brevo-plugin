<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\EventListener;

use Odiseo\SyliusBrevoPlugin\Catalog\CatalogModule;
use Odiseo\SyliusBrevoPlugin\Catalog\Message\ActivateEcommerce;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;

/** Saving a configuration with the catalog module on (re)activates Brevo Ecommerce, after the response. */
final class EcommerceActivationListener
{
    public function __construct(
        private readonly BrevoMessageDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(GenericEvent $event): void
    {
        $configuration = $event->getSubject();
        if (!$configuration instanceof ChannelConfigurationInterface || !$configuration->isEnabled() || !$configuration->hasModule(CatalogModule::CODE)) {
            return;
        }

        $channelCode = $configuration->getChannel()?->getCode();
        if (null !== $channelCode) {
            $this->dispatcher->dispatch(new ActivateEcommerce($channelCode));
        }
    }
}
