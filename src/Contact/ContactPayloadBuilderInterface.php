<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Odiseo\SyliusBrevoPlugin\Client\Model\ContactData;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

interface ContactPayloadBuilderInterface
{
    /** Email, ext_id and every mapped attribute, under Brevo names. */
    public function build(CustomerInterface $customer, ChannelInterface $channel): ContactData;

    /**
     * Without a channel, under the names shared by every channel.
     *
     * @return array<string, string> Brevo attribute name => Attribute::TYPE_*
     */
    public function getAttributeTypes(?ChannelInterface $channel = null): array;

    /** @return array<string, string> contact data key (first_name...) => Attribute::TYPE_* */
    public function getKeyTypes(): array;
}
