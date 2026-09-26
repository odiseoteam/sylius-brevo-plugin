<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Sylius\Component\Core\Model\CustomerInterface;

/** Brevo lists a customer belongs to: the customers list, plus the newsletter one when subscribed. */
final class ContactLists
{
    /** @return list<int> */
    public static function of(CustomerInterface $customer, BrevoSettings $settings): array
    {
        $listIds = [];
        if (null !== $settings->customersListId) {
            $listIds[] = $settings->customersListId;
        }

        if ($settings->hasNewsletter() && null !== $settings->newsletterListId && $customer->isSubscribedToNewsletter()) {
            $listIds[] = $settings->newsletterListId;
        }

        return array_values(array_unique($listIds));
    }
}
