<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Attribute;

use Sylius\Component\Core\Model\ChannelInterface;

/** Brevo attribute name of each contact data key. Decorate `odiseo_brevo.contact.attribute_mapping` to change it per channel. */
interface AttributeMappingInterface
{
    /** Null when the key is not sent. Without a channel, the mapping shared by every channel. */
    public function brevoName(string $key, ?ChannelInterface $channel = null): ?string;
}
