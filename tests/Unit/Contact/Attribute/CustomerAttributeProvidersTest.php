<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Contact\Attribute;

use Odiseo\SyliusBrevoPlugin\Contact\Attribute\AddressAttributeProvider;
use Odiseo\SyliusBrevoPlugin\Contact\Attribute\CustomerDetailsAttributeProvider;
use Odiseo\SyliusBrevoPlugin\Contact\Attribute\OrderStatsAttributeProvider;
use Odiseo\SyliusBrevoPlugin\Contact\ContactAddressResolverInterface;
use Odiseo\SyliusBrevoPlugin\Contact\CustomerOrderStats;
use Odiseo\SyliusBrevoPlugin\Contact\CustomerOrderStatsProviderInterface;
use Odiseo\SyliusBrevoPlugin\Formatter\DateFormatter;
use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatter;
use Odiseo\SyliusBrevoPlugin\Phone\PhoneNumberNormalizer;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Customer\Model\CustomerGroup;
use Sylius\Component\Locale\Model\Locale;

final class CustomerAttributeProvidersTest extends TestCase
{
    private Channel $channel;

    protected function setUp(): void
    {
        $currency = new Currency();
        $currency->setCode('ARS');
        $locale = new Locale();
        $locale->setCode('es_AR');

        $this->channel = new Channel();
        $this->channel->setCode('WEB-AR');
        $this->channel->setBaseCurrency($currency);
        $this->channel->setDefaultLocale($locale);
    }

    public function testCustomerDetails(): void
    {
        $group = new CustomerGroup();
        $group->setCode('wholesale');

        $customer = new Customer();
        $customer->setFirstName('Carrot');
        $customer->setLastName('Ironfoundersson');
        $customer->setGender('m');
        $customer->setBirthday(new \DateTime('1990-05-17'));
        $customer->setGroup($group);
        $customer->setDefaultAddress($this->address());

        $provider = new CustomerDetailsAttributeProvider(new PhoneNumberNormalizer(), new DateFormatter(), $this->addressResolver());

        self::assertSame([
            'first_name' => 'Carrot',
            'last_name' => 'Ironfoundersson',
            'phone' => '+5491122334455',
            'gender' => 'm',
            'birthday' => '1990-05-17',
            'customer_group' => 'wholesale',
            'channel' => 'WEB-AR',
        ], $provider->provide($customer, $this->channel));
        self::assertSame(array_keys($provider->getAttributeTypes()), array_keys($provider->provide($customer, $this->channel)));
    }

    public function testUnknownValuesAreCleared(): void
    {
        $provider = new CustomerDetailsAttributeProvider(new PhoneNumberNormalizer(), new DateFormatter(), $this->addressResolver());

        $attributes = $provider->provide(new Customer(), $this->channel);

        self::assertNull($attributes['phone']);
        self::assertNull($attributes['gender']);
        self::assertNull($attributes['birthday']);
    }

    public function testGuestsTakeNamesAndPhoneFromTheirOrderAddress(): void
    {
        $address = $this->address();
        $address->setFirstName('Jon');
        $address->setLastName('Snow');

        $provider = new CustomerDetailsAttributeProvider(new PhoneNumberNormalizer(), new DateFormatter(), $this->addressResolver($address));
        $attributes = $provider->provide(new Customer(), $this->channel);

        self::assertSame(['Jon', 'Snow', '+5491122334455'], [$attributes['first_name'], $attributes['last_name'], $attributes['phone']]);
    }

    public function testDefaultAddress(): void
    {
        $customer = new Customer();
        $customer->setDefaultAddress($this->address());

        self::assertSame([
            'city' => 'Buenos Aires',
            'province' => 'CABA',
            'country' => 'AR',
            'postcode' => 'C1425',
        ], (new AddressAttributeProvider($this->addressResolver()))->provide($customer, $this->channel));
    }

    public function testOrderStats(): void
    {
        $stats = $this->createStub(CustomerOrderStatsProviderInterface::class);
        $stats->method('getStats')->willReturn(new CustomerOrderStats(3, 300050, new \DateTimeImmutable('2026-01-10 10:00'), new \DateTimeImmutable('2026-09-01 18:00'), 'en_US'));

        $provider = new OrderStatsAttributeProvider($stats, new MoneyFormatter(), new DateFormatter());

        self::assertSame([
            'orders_count' => 3,
            'total_spent' => 3000.5,
            'average_order_value' => 1000.16,
            'first_order_date' => '2026-01-10',
            'last_order_date' => '2026-09-01',
            'locale' => 'en_US',
        ], $provider->provide(new Customer(), $this->channel));
    }

    public function testNoOrdersYet(): void
    {
        $stats = $this->createStub(CustomerOrderStatsProviderInterface::class);
        $stats->method('getStats')->willReturn(new CustomerOrderStats());

        $attributes = (new OrderStatsAttributeProvider($stats, new MoneyFormatter(), new DateFormatter()))->provide(new Customer(), $this->channel);

        self::assertSame(0, $attributes['orders_count']);
        self::assertSame(0.0, $attributes['total_spent']);
        self::assertNull($attributes['last_order_date']);
        self::assertSame('es_AR', $attributes['locale']);
    }

    /** Resolves the customer's default address, or the given order address when it has none. */
    private function addressResolver(?Address $orderAddress = null): ContactAddressResolverInterface
    {
        $resolver = $this->createStub(ContactAddressResolverInterface::class);
        $resolver->method('resolve')->willReturnCallback(
            static fn (CustomerInterface $customer): ?AddressInterface => $customer->getDefaultAddress() ?? $orderAddress,
        );

        return $resolver;
    }

    private function address(): Address
    {
        $address = new Address();
        $address->setCity('Buenos Aires');
        $address->setProvinceName('CABA');
        $address->setCountryCode('AR');
        $address->setPostcode('C1425');
        $address->setPhoneNumber('011 15 2233-4455');

        return $address;
    }
}
