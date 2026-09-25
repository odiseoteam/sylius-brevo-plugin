<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Sylius\Component\Core\Model\CustomerInterface;

interface CustomerBatchesInterface
{
    /**
     * Customers with an email, by id, in batches. The entity manager is cleared between batches.
     *
     * @return iterable<list<CustomerInterface>>
     */
    public function batches(CustomerFilter $filter, int $size): iterable;

    public function count(CustomerFilter $filter): int;
}
