<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Contact;

use Odiseo\SyliusBrevoPlugin\Client\Model\ContactData;
use Odiseo\SyliusBrevoPlugin\Contact\Attribute\AttributeMapping;
use Odiseo\SyliusBrevoPlugin\Contact\Attribute\AttributeMappingInterface;
use Odiseo\SyliusBrevoPlugin\Contact\Attribute\ContactAttributeProviderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactPayloadBuilder;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\CustomerInterface;

final class ContactPayloadBuilderTest extends TestCase
{
    /** Unknown values (null, "") are left out, so Brevo keeps what it had. */
    public function testItMapsEveryProviderUnderBrevoNames(): void
    {
        $builder = new ContactPayloadBuilder([
            $this->provider(['first_name' => 'text', 'birthday' => 'date'], ['first_name' => 'Carrot', 'birthday' => '1990-05-17']),
            $this->provider(['first_name' => 'text', 'loyalty_tier' => 'text', 'phone' => 'text', 'city' => 'text'], ['first_name' => 'Captain', 'loyalty_tier' => 'gold', 'phone' => null, 'city' => '']),
        ], new AttributeMapping(['birthday' => false]));

        $customer = new Customer();
        $customer->setEmail('carrot@example.com');
        (new \ReflectionProperty(Customer::class, 'id'))->setValue($customer, 7);

        $data = $builder->build($customer, new Channel());

        self::assertSame('carrot@example.com', $data->email);
        self::assertSame('7', $data->extId);
        self::assertSame(['FIRSTNAME' => 'Captain', 'LOYALTY_TIER' => 'gold'], $data->attributes);
        self::assertSame(['FIRSTNAME' => 'text', 'LOYALTY_TIER' => 'text', 'SMS' => 'text', 'CITY' => 'text'], $builder->getAttributeTypes());
    }

    public function testTheMappingCanChangePerChannel(): void
    {
        $mapping = new class() implements AttributeMappingInterface {
            public function brevoName(string $key, ?ChannelInterface $channel = null): string
            {
                return 'ES' === $channel?->getCode() ? 'NOMBRE' : 'FIRSTNAME';
            }
        };
        $builder = new ContactPayloadBuilder([$this->provider(['first_name' => 'text'], ['first_name' => 'Carrot'])], $mapping);
        $channel = new Channel();
        $channel->setCode('ES');
        $customer = new Customer();
        $customer->setEmail('carrot@example.com');

        self::assertSame(['NOMBRE' => 'Carrot'], $builder->build($customer, $channel)->attributes);
        self::assertSame(['NOMBRE' => 'text'], $builder->getAttributeTypes($channel));
        self::assertSame(['FIRSTNAME' => 'text'], $builder->getAttributeTypes());
        self::assertSame(['first_name' => 'text'], $builder->getKeyTypes());
    }

    public function testOnlyAttributesTheAccountHasAreKept(): void
    {
        $data = new ContactData(email: 'a@example.com', attributes: ['FIRSTNAME' => 'A', 'TOTAL_SPENT' => 10.0]);

        self::assertSame(['FIRSTNAME' => 'A'], $data->withAttributesIn(['firstname', 'LASTNAME'])->attributes);
    }

    /**
     * @param array<string, string> $types
     * @param array<string, scalar|null> $values
     */
    private function provider(array $types, array $values): ContactAttributeProviderInterface
    {
        return new class($types, $values) implements ContactAttributeProviderInterface {
            /**
             * @param array<string, string> $types
             * @param array<string, scalar|null> $values
             */
            public function __construct(private readonly array $types, private readonly array $values)
            {
            }

            public function getAttributeTypes(): array
            {
                return $this->types;
            }

            public function provide(CustomerInterface $customer, ChannelInterface $channel): array
            {
                return $this->values;
            }
        };
    }
}
