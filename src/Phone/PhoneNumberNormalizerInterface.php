<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Phone;

use Sylius\Component\Core\Model\ChannelInterface;

interface PhoneNumberNormalizerInterface
{
    /**
     * Returns the number in E.164 (e.g. +5491122334455), or null when it can't be parsed as a valid number.
     *
     * Numbers without an international prefix take their country from, in order: $countryCode,
     * the channel's only country, the configured default region.
     *
     * @param string|null $countryCode ISO 3166-1 alpha-2, usually from the address
     */
    public function normalize(?string $phoneNumber, ?string $countryCode = null, ?ChannelInterface $channel = null): ?string;
}
