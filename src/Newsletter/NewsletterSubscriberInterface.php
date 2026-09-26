<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Newsletter;

use Sylius\Component\Core\Model\ChannelInterface;

interface NewsletterSubscriberInterface
{
    /**
     * Subscribes the customer with this email, creating it when there is none. With double opt-in
     * on the channel, an unconfirmed email (not the logged-in customer's) gets a confirmation link
     * first.
     */
    public function subscribe(string $email, ChannelInterface $channel, string $localeCode, bool $confirmed = false): SubscriptionResult;
}
