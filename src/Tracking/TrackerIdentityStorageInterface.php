<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking;

use Sylius\Component\Core\Model\CustomerInterface;

/** Visitors to identify in the tracker on the next page they see. */
interface TrackerIdentityStorageInterface
{
    public function remember(CustomerInterface $customer): void;

    /**
     * The identifiers to send, once.
     *
     * @return array{email_id: string, ext_id?: string}|null
     */
    public function pull(): ?array;
}
