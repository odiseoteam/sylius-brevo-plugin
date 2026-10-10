<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\EventListener;

use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition\CustomerRegistered;
use Odiseo\SyliusBrevoPlugin\Tracking\Message\TrackCustomerEvent;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackingModule;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;

/** Registrations in the shop, in the channel they happen. */
final class CustomerRegisteredListener
{
    public function __construct(
        private readonly ChannelContextInterface $channelContext,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly BrevoMessageDispatcherInterface $dispatcher,
    ) {
    }

    public function onRegister(GenericEvent $event): void
    {
        $customer = $event->getSubject();
        $customerId = $customer instanceof CustomerInterface ? $customer->getId() : null;

        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return;
        }

        if (is_int($customerId) && $channel instanceof ChannelInterface && true === $this->configurationProvider->getSettings($channel)?->hasModule(TrackingModule::CODE)) {
            $this->dispatcher->dispatch(new TrackCustomerEvent((string) $channel->getCode(), $customerId, CustomerRegistered::CODE));
        }
    }
}
