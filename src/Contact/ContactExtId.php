<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Sylius\Component\Core\Model\CustomerInterface;

/** The contact's ext_id: the customer id, stable across email changes. */
final class ContactExtId
{
    public static function of(CustomerInterface $customer): string
    {
        $id = $customer->getId();

        return is_scalar($id) ? (string) $id : '';
    }
}
