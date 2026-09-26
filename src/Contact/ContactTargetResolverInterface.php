<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

interface ContactTargetResolverInterface
{
    /**
     * One channel per Brevo account with the contacts module on: a customer is synced once per
     * account, through the channel of its last order when that one shares the account. Guests
     * count only where guests are synced, or the newsletter is on and they subscribed, unless $withGuest.
     *
     * @return list<ChannelInterface>
     */
    public function resolve(CustomerInterface $customer, bool $withGuest = false): array;

    /**
     * One channel per Brevo account with the contacts module on.
     *
     * @return list<ChannelInterface>
     */
    public function accounts(): array;
}
