<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\EventListener;

use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackerIdentityStorageInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackingModule;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/** Identifies the visitor in the tracker after signing in, registering or giving an email in the checkout. */
final class TrackerIdentityListener
{
    public function __construct(
        private readonly ChannelContextInterface $channelContext,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly TrackerIdentityStorageInterface $identityStorage,
    ) {
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user instanceof ShopUserInterface) {
            $this->remember($user->getCustomer());
        }
    }

    public function onRegister(GenericEvent $event): void
    {
        $this->remember($event->getSubject());
    }

    public function onAddress(GenericEvent $event): void
    {
        $order = $event->getSubject();
        $this->remember($order instanceof OrderInterface ? $order->getCustomer() : null);
    }

    private function remember(mixed $customer): void
    {
        if ($customer instanceof CustomerInterface && $this->tracking()) {
            $this->identityStorage->remember($customer);
        }
    }

    private function tracking(): bool
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return false;
        }

        return $channel instanceof ChannelInterface && true === $this->configurationProvider->getSettings($channel)?->hasModule(TrackingModule::CODE);
    }
}
