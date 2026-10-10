<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

use Odiseo\SyliusBrevoPlugin\Client\Api\EventsApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Model\EventData;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactExtId;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class TrackingEventSender implements TrackingEventSenderInterface
{
    public function __construct(
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly TrackingEventResolverInterface $eventResolver,
        private readonly EventsApiInterface $eventsApi,
    ) {
    }

    public function send(string $eventCode, object $subject, ChannelInterface $channel, CustomerInterface $customer): void
    {
        $email = $customer->getEmail();
        $settings = $this->configurationProvider->getSettings($channel);
        $event = null === $email ? null : $this->eventResolver->resolve($eventCode, $subject, $channel);
        if (null === $email || null === $settings || null === $event) {
            return;
        }

        $this->eventsApi->track($settings->credentials, new EventData(
            $event->name,
            array_filter(['email_id' => $email, 'ext_id' => ContactExtId::of($customer)], static fn (string $value): bool => '' !== $value),
            $event->properties,
        ));
    }
}
