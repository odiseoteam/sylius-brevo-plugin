<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Phone;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Sylius\Component\Core\Model\ChannelInterface;

final class PhoneNumberNormalizer implements PhoneNumberNormalizerInterface
{
    private readonly PhoneNumberUtil $phoneNumberUtil;

    public function __construct(
        private readonly ?string $defaultRegion = null,
        ?PhoneNumberUtil $phoneNumberUtil = null,
    ) {
        $this->phoneNumberUtil = $phoneNumberUtil ?? PhoneNumberUtil::getInstance();
    }

    public function normalize(?string $phoneNumber, ?string $countryCode = null, ?ChannelInterface $channel = null): ?string
    {
        if (null === $phoneNumber || '' === trim($phoneNumber)) {
            return null;
        }

        $region = $countryCode ?? $this->channelCountry($channel) ?? $this->defaultRegion;

        try {
            $parsed = $this->phoneNumberUtil->parse($phoneNumber, null === $region ? null : strtoupper($region));
        } catch (NumberParseException) {
            return null;
        }

        if (!$this->phoneNumberUtil->isValidNumber($parsed)) {
            return null;
        }

        return $this->phoneNumberUtil->format($parsed, PhoneNumberFormat::E164);
    }

    private function channelCountry(?ChannelInterface $channel): ?string
    {
        $countries = $channel?->getCountries();
        if (null === $countries || 1 !== $countries->count()) {
            return null;
        }

        $country = $countries->first();

        return false === $country ? null : $country->getCode();
    }
}
