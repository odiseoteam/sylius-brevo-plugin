<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Phone;

use Odiseo\SyliusBrevoPlugin\Phone\PhoneNumberNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Addressing\Model\Country;
use Sylius\Component\Core\Model\Channel;

final class PhoneNumberNormalizerTest extends TestCase
{
    #[DataProvider('numbers')]
    public function testItNormalizesToE164(?string $number, ?string $countryCode, ?string $defaultRegion, ?string $expected): void
    {
        self::assertSame($expected, (new PhoneNumberNormalizer($defaultRegion))->normalize($number, $countryCode));
    }

    /** @return iterable<string, array{?string, ?string, ?string, ?string}> */
    public static function numbers(): iterable
    {
        yield 'international prefix' => ['+54 9 11 2233-4455', null, null, '+5491122334455'];
        yield 'national with country' => ['011 15 2233-4455', 'AR', null, '+5491122334455'];
        yield 'lowercase country' => ['(202) 555-0125', 'us', null, '+12025550125'];
        yield 'default region' => ['(202) 555-0125', null, 'US', '+12025550125'];
        yield 'country wins over default' => ['0612345678', 'FR', 'US', '+33612345678'];
        yield 'national without region' => ['(202) 555-0125', null, null, null];
        yield 'invalid' => ['123', 'US', null, null];
        yield 'garbage' => ['not a phone', 'US', null, null];
        yield 'empty' => ['  ', 'US', null, null];
        yield 'null' => [null, 'US', null, null];
    }

    public function testItFallsBackToTheChannelsOnlyCountry(): void
    {
        $normalizer = new PhoneNumberNormalizer('US');

        self::assertSame('+5491122334455', $normalizer->normalize('011 15 2233-4455', null, $this->channel('AR')));
        self::assertSame('+33612345678', $normalizer->normalize('0612345678', 'FR', $this->channel('AR')));
    }

    public function testItIgnoresChannelsWithSeveralOrNoCountries(): void
    {
        $normalizer = new PhoneNumberNormalizer('US');

        self::assertSame('+12025550125', $normalizer->normalize('(202) 555-0125', null, $this->channel('AR', 'UY')));
        self::assertSame('+12025550125', $normalizer->normalize('(202) 555-0125', null, $this->channel()));
    }

    private function channel(string ...$countryCodes): Channel
    {
        $channel = new Channel();
        foreach ($countryCodes as $code) {
            $country = new Country();
            $country->setCode($code);
            $channel->addCountry($country);
        }

        return $channel;
    }
}
