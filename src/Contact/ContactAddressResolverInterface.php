<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;

interface ContactAddressResolverInterface
{
    /**
     * The customer's default address or, lacking one (e.g. guests), the billing address of its most
     * recent order, the cart in checkout included.
     */
    public function resolve(CustomerInterface $customer): ?AddressInterface;
}
