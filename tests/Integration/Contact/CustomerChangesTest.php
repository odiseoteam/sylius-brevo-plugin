<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Integration\Contact;

use Doctrine\ORM\EntityManagerInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Contact\ContactExtId;
use Odiseo\SyliusBrevoPlugin\Contact\ContactPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Sylius\Component\Core\Model\Address;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;

/** Outside a request (CLI, imports), customer changes reach Brevo right after the flush. */
final class CustomerChangesTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private FakeBrevoHttpClient $client;

    private Channel $channel;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $this->entityManager->getConnection()->beginTransaction();
        // Only this test's channel is configured (Behat leaves its last scenario behind).
        $this->entityManager->getConnection()->executeStatement('DELETE FROM odiseo_brevo_channel_configuration');

        $client = self::getContainer()->get('odiseo_brevo.client.http.transport');
        self::assertInstanceOf(FakeBrevoHttpClient::class, $client);
        $this->client = $client;
        $this->client->reset();

        $builder = self::getContainer()->get(ContactPayloadBuilderInterface::class);
        self::assertInstanceOf(ContactPayloadBuilderInterface::class, $builder);
        $attributes = array_map(static fn (string $name): array => ['name' => $name, 'category' => 'normal'], array_keys($builder->getAttributeTypes()));
        $this->client->respondAlways('GET', '/contacts/attributes', new BrevoResponse(200, ['attributes' => $attributes]));

        $this->configureChannel();
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();

        parent::tearDown();
    }

    public function testANewCustomerIsSentOnceItIsFlushed(): void
    {
        $customer = $this->customer('vimes@example.com');

        $request = $this->client->lastRequest();
        self::assertSame(['PUT', '/contacts/' . ContactExtId::of($customer)], [$request?->method, $request?->path]);
        self::assertSame('Vimes', $this->attribute('LASTNAME'));
        self::assertSame('BREVO_TEST', $this->attribute('CHANNEL'));
    }

    public function testChangingTheDefaultAddressUpdatesTheContact(): void
    {
        $customer = $this->customer('vimes@example.com');
        $this->client->reset();

        $address = new Address();
        $address->setFirstName('Sam');
        $address->setLastName('Vimes');
        $address->setStreet('Pseudopolis Yard');
        $address->setCity('Ankh-Morpork');
        $address->setPostcode('AM1');
        $address->setCountryCode('AR');
        $customer->setDefaultAddress($address);
        $this->entityManager->flush();

        self::assertSame('Ankh-Morpork', $this->attribute('CITY'));
    }

    public function testAGuestOrderFillsTheContactAndItsStateChangesResync(): void
    {
        $customer = new Customer();
        $customer->setEmail('guest@example.com');

        $billingAddress = new Address();
        $billingAddress->setFirstName('Nobby');
        $billingAddress->setLastName('Nobbs');
        $billingAddress->setStreet('Cockbill Street');
        $billingAddress->setCity('Ankh-Morpork');
        $billingAddress->setPostcode('AM2');
        $billingAddress->setCountryCode('AR');

        $order = new Order();
        $order->setChannel($this->channel);
        $order->setCurrencyCode('USD');
        $order->setLocaleCode('en_US');
        $order->setCustomer($customer);
        $order->setBillingAddress($billingAddress);

        $this->entityManager->persist($customer);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        self::assertSame(['Nobby', 'Nobbs', 'Ankh-Morpork'], [$this->attribute('FIRSTNAME'), $this->attribute('LASTNAME'), $this->attribute('CITY')]);

        $this->client->reset();
        $order->setNotes('Leave it at the Watch House');
        $this->entityManager->flush();
        self::assertSame([], $this->client->requests(), 'Unrelated order changes are not sent.');

        $order->setCheckoutState('completed');
        $this->entityManager->flush();
        self::assertCount(1, $this->client->requests('PUT'));
    }

    public function testRemovingACustomerDeletesItsContactWhenConfigured(): void
    {
        $customer = $this->customer('vimes@example.com');
        $extId = ContactExtId::of($customer);

        $this->entityManager->remove($customer);
        $this->entityManager->flush();

        $request = $this->client->lastRequest();
        self::assertSame(['DELETE', '/contacts/' . $extId, ['identifierType' => 'ext_id']], [$request?->method, $request?->path, $request?->query]);
    }

    private function attribute(string $name): mixed
    {
        $attributes = $this->client->lastRequest()?->json['attributes'] ?? null;
        self::assertIsArray($attributes);

        return $attributes[$name] ?? null;
    }

    private function customer(string $email): Customer
    {
        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setFirstName('Sam');
        $customer->setLastName('Vimes');
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }

    private function configureChannel(): void
    {
        $locale = $this->entityManager->getRepository(Locale::class)->findOneBy(['code' => 'en_US']) ?? (static function (): Locale {
            $locale = new Locale();
            $locale->setCode('en_US');

            return $locale;
        })();
        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']) ?? (static function (): Currency {
            $currency = new Currency();
            $currency->setCode('USD');

            return $currency;
        })();

        $channel = $this->channel = new Channel();
        $channel->setCode('BREVO_TEST');
        $channel->setName('Brevo test');
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setDefaultLocale($locale);
        $channel->setBaseCurrency($currency);

        $configuration = new ChannelConfiguration();
        $configuration->setChannel($channel);
        $configuration->setApiKey('xkeysib-test');
        $configuration->setModules(['contacts']);
        $configuration->setDeletingContactsOfRemovedCustomers(true);

        $this->entityManager->persist($locale);
        $this->entityManager->persist($currency);
        $this->entityManager->persist($channel);
        $this->entityManager->persist($configuration);
        $this->entityManager->flush();
    }
}
