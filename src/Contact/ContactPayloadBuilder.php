<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Odiseo\SyliusBrevoPlugin\Client\Model\ContactData;
use Odiseo\SyliusBrevoPlugin\Contact\Attribute\AttributeMappingInterface;
use Odiseo\SyliusBrevoPlugin\Contact\Attribute\ContactAttributeProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

final class ContactPayloadBuilder implements ContactPayloadBuilderInterface
{
    /** @param iterable<ContactAttributeProviderInterface> $providers later ones win on the same key */
    public function __construct(
        private readonly iterable $providers,
        private readonly AttributeMappingInterface $mapping,
    ) {
    }

    public function build(CustomerInterface $customer, ChannelInterface $channel): ContactData
    {
        $attributes = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->provide($customer, $channel) as $key => $value) {
                $name = $this->mapping->brevoName($key, $channel);
                if (null !== $name && null !== $value && '' !== $value) {
                    $attributes[$name] = $value;
                }
            }
        }

        return new ContactData(
            email: $customer->getEmail(),
            extId: ContactExtId::of($customer),
            attributes: $attributes,
        );
    }

    public function getAttributeTypes(?ChannelInterface $channel = null): array
    {
        $types = [];
        foreach ($this->getKeyTypes() as $key => $type) {
            $name = $this->mapping->brevoName($key, $channel);
            if (null !== $name) {
                $types[$name] = $type;
            }
        }

        return $types;
    }

    public function getKeyTypes(): array
    {
        $types = [];
        foreach ($this->providers as $provider) {
            $types = [...$types, ...$provider->getAttributeTypes()];
        }

        return $types;
    }
}
