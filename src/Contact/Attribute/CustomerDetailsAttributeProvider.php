<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Attribute;

use Odiseo\SyliusBrevoPlugin\Client\Model\Attribute;
use Odiseo\SyliusBrevoPlugin\Contact\ContactAddressResolverInterface;
use Odiseo\SyliusBrevoPlugin\Formatter\DateFormatterInterface;
use Odiseo\SyliusBrevoPlugin\Phone\PhoneNumberNormalizerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Customer\Model\CustomerInterface as BaseCustomerInterface;

final class CustomerDetailsAttributeProvider implements ContactAttributeProviderInterface
{
    public function __construct(
        private readonly PhoneNumberNormalizerInterface $phoneNumberNormalizer,
        private readonly DateFormatterInterface $dateFormatter,
        private readonly ContactAddressResolverInterface $addressResolver,
    ) {
    }

    public function getAttributeTypes(): array
    {
        return [
            'first_name' => Attribute::TYPE_TEXT,
            'last_name' => Attribute::TYPE_TEXT,
            'phone' => Attribute::TYPE_TEXT,
            'gender' => Attribute::TYPE_TEXT,
            'birthday' => Attribute::TYPE_DATE,
            'customer_group' => Attribute::TYPE_TEXT,
            'channel' => Attribute::TYPE_TEXT,
        ];
    }

    public function provide(CustomerInterface $customer, ChannelInterface $channel): array
    {
        // Guests only have names and phone on their order addresses.
        $address = $this->addressResolver->resolve($customer);
        $birthday = $customer->getBirthday();

        return [
            'first_name' => $customer->getFirstName() ?? $address?->getFirstName(),
            'last_name' => $customer->getLastName() ?? $address?->getLastName(),
            'phone' => $this->phoneNumberNormalizer->normalize(
                $customer->getPhoneNumber() ?? $address?->getPhoneNumber(),
                $address?->getCountryCode(),
                $channel,
            ),
            'gender' => BaseCustomerInterface::UNKNOWN_GENDER === $customer->getGender() ? null : $customer->getGender(),
            'birthday' => null === $birthday ? null : $this->dateFormatter->formatDate($birthday),
            'customer_group' => $customer->getGroup()?->getCode(),
            'channel' => $channel->getCode(),
        ];
    }
}
