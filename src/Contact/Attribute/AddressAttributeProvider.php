<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Attribute;

use Odiseo\SyliusBrevoPlugin\Client\Model\Attribute;
use Odiseo\SyliusBrevoPlugin\Contact\ContactAddressResolverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;

/** From the default address or, for guests, the billing address of the last order. */
final class AddressAttributeProvider implements ContactAttributeProviderInterface
{
    public function __construct(
        private readonly ContactAddressResolverInterface $addressResolver,
    ) {
    }

    public function getAttributeTypes(): array
    {
        return [
            'city' => Attribute::TYPE_TEXT,
            'province' => Attribute::TYPE_TEXT,
            'country' => Attribute::TYPE_TEXT,
            'postcode' => Attribute::TYPE_TEXT,
        ];
    }

    public function provide(CustomerInterface $customer, ChannelInterface $channel): array
    {
        $address = $this->addressResolver->resolve($customer);

        return [
            'city' => $address?->getCity(),
            'province' => $address?->getProvinceName() ?? $address?->getProvinceCode(),
            'country' => $address?->getCountryCode(),
            'postcode' => $address?->getPostcode(),
        ];
    }
}
