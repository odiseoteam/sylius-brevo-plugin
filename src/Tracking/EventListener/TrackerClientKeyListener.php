<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\EventListener;

use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Message\FetchTrackerClientKey;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackingModule;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;

/** Saving a configuration with the tracking module on and no client key fetches it, after the response. */
final class TrackerClientKeyListener
{
    public function __construct(
        private readonly BrevoMessageDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(GenericEvent $event): void
    {
        $configuration = $event->getSubject();
        if (!$configuration instanceof ChannelConfigurationInterface || !$configuration->isEnabled() || !$configuration->hasModule(TrackingModule::CODE) || null !== $configuration->getTrackerClientKey()) {
            return;
        }

        $channelCode = $configuration->getChannel()?->getCode();
        if (null !== $channelCode) {
            $this->dispatcher->dispatch(new FetchTrackerClientKey($channelCode));
        }
    }
}
