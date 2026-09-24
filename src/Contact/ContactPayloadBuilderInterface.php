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

    /** @return array<string, string> Brevo attribute name => Attribute::TYPE_* */
    public function getAttributeTypes(): array;
}
