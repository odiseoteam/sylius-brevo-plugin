<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Attribute;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/**
 * Adds contact attributes under internal keys (e.g. "first_name"); AttributeMapping turns them into
 * Brevo names. Tag: `odiseo_brevo.contact_attribute_provider`.
 */
interface ContactAttributeProviderInterface
{
    /**
     * Brevo type of each key this provider fills, used to create missing attributes.
     *
     * @return array<string, string> key => Attribute::TYPE_*
     */
    public function getAttributeTypes(): array;

    /**
     * Values ready for Brevo: dates as Y-m-d, amounts as decimals. Null or "" means unknown: it's
     * not sent, so Brevo keeps what it had.
     *
     * @return array<string, scalar|null>
     */
    public function provide(CustomerInterface $customer, ChannelInterface $channel): array;
}
