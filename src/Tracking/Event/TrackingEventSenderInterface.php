<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/** Sends a server event to the customer's Brevo contact. Calls Brevo: use it from a message handler. */
interface TrackingEventSenderInterface
{
    /**
     * Nothing is sent when the event is off or unknown, doesn't support the subject, or the customer has no email.
     *
     * @throws BrevoException
     */
    public function send(string $eventCode, object $subject, ChannelInterface $channel, CustomerInterface $customer): void;
}
