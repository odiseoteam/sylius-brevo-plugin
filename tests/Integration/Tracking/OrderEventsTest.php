<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Integration\Tracking;

use Doctrine\ORM\EntityManagerInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\Model\OrderItemUnit;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Outside a request, cart and order events reach Brevo right after the flush. */
final class OrderEventsTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private FakeBrevoHttpClient $client;

    private Channel $channel;

    private ProductVariant $variant;

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

        $this->configureStore();
        $this->client->reset();
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();

        parent::tearDown();
    }

    public function testACartIsSentOnceItHasItemsAndAnEmail(): void
    {
        $cart = $this->cart();
        self::assertSame([], $this->events(), 'An empty cart is not sent.');

        $this->addItem($cart, 2);
        self::assertSame([], $this->events(), 'A cart without email is not sent.');

        $customer = new Customer();
        $customer->setEmail('colon@example.com');
        $cart->setCustomer($customer);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        $event = $this->lastEvent('cart_updated');
        self::assertIsInt($customer->getId());
        self::assertSame(['email_id' => 'colon@example.com', 'ext_id' => (string) $customer->getId()], $event['identifiers'] ?? null);
        $properties = $event['event_properties'] ?? null;
        self::assertIsArray($properties);
        self::assertSame($cart->getTokenValue(), $properties['cart_id'] ?? null);
        self::assertSame(39.98, $properties['total'] ?? null);
        self::assertSame('USD', $properties['currency'] ?? null);
        self::assertIsString($properties['url'] ?? null);
        self::assertStringEndsWith('/en_US/cart/', $properties['url']);
        self::assertIsArray($properties['items'] ?? null);
        self::assertCount(1, $properties['items']);
        $item = $properties['items'][0];
        self::assertIsArray($item);
        self::assertIsString($item['url'] ?? null);
        self::assertStringEndsWith('/en_US/products/brevo-tee', $item['url']);
        unset($item['url']);
        self::assertSame(['product_id' => 'brevo_tee_m', 'name' => 'Brevo tee', 'quantity' => 2, 'price' => 19.99], $item);
    }

    public function testEmptyingTheCartDeletesIt(): void
    {
        $cart = $this->cart('colon@example.com');
        $item = $this->addItem($cart);
        $this->client->reset();

        $cart->removeItem($item);
        $this->entityManager->flush();

        self::assertCount(1, $this->events());
        $properties = $this->lastEvent('cart_deleted')['event_properties'] ?? null;
        self::assertIsArray($properties);
        self::assertSame(0.0, $properties['total'] ?? null);
    }

    public function testCompletingTheCheckoutSendsTheOrderInsteadOfTheCart(): void
    {
        $cart = $this->cart('colon@example.com');
        $this->addItem($cart);
        $this->client->reset();

        $cart->setState(OrderInterface::STATE_NEW);
        $cart->setCheckoutState(OrderCheckoutStates::STATE_COMPLETED);
        $cart->setCheckoutCompletedAt(new \DateTime());
        $cart->setNumber('000000042');
        $this->entityManager->flush();

        self::assertCount(1, $this->events());
        $properties = $this->lastEvent('order_completed')['event_properties'] ?? null;
        self::assertIsArray($properties);
        self::assertSame('000000042', $properties['order_id'] ?? null);
        self::assertSame(19.99, $properties['total'] ?? null);
    }

    /** @return list<array<string, mixed>> */
    private function events(): array
    {
        $events = [];
        foreach ($this->client->requests('POST', '/events') as $request) {
            /** @var array<string, mixed> $event */
            $event = (array) $request->json;
            $events[] = $event;
        }

        return $events;
    }

    /** @return array<string, mixed> */
    private function lastEvent(string $name): array
    {
        $events = $this->events();
        $event = end($events);
        self::assertIsArray($event, 'Brevo received no event.');
        self::assertSame($name, $event['event_name'] ?? null);

        return $event;
    }

    private function cart(?string $email = null): Order
    {
        $cart = new Order();
        $cart->setChannel($this->channel);
        $cart->setCurrencyCode('USD');
        $cart->setLocaleCode('en_US');
        $cart->setTokenValue(bin2hex(random_bytes(8)));
        if (null !== $email) {
            $customer = new Customer();
            $customer->setEmail($email);
            $cart->setCustomer($customer);
            $this->entityManager->persist($customer);
        }

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    private function addItem(Order $cart, int $quantity = 1): OrderItem
    {
        $item = new OrderItem();
        $item->setVariant($this->variant);
        $item->setUnitPrice(1999);
        for ($i = 0; $i < $quantity; ++$i) {
            new OrderItemUnit($item);
        }
        $cart->addItem($item);
        $this->entityManager->flush();

        return $item;
    }

    private function configureStore(): void
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

        $this->channel = new Channel();
        $this->channel->setCode('BREVO_TEST');
        $this->channel->setName('Brevo test');
        $this->channel->setTaxCalculationStrategy('order_items_based');
        $this->channel->setDefaultLocale($locale);
        $this->channel->setBaseCurrency($currency);

        $configuration = new ChannelConfiguration();
        $configuration->setChannel($this->channel);
        $configuration->setApiKey('xkeysib-test');
        $configuration->setModules(['tracking']);

        $product = new Product();
        $product->setCurrentLocale('en_US');
        $product->setFallbackLocale('en_US');
        $product->setCode('brevo_tee');
        $product->setName('Brevo tee');
        $product->setSlug('brevo-tee');
        $product->addChannel($this->channel);
        $this->variant = new ProductVariant();
        $this->variant->setCurrentLocale('en_US');
        $this->variant->setFallbackLocale('en_US');
        $this->variant->setCode('brevo_tee_m');
        $pricing = new ChannelPricing();
        $pricing->setChannelCode('BREVO_TEST');
        $pricing->setPrice(1999);
        $this->variant->addChannelPricing($pricing);
        $product->addVariant($this->variant);

        $this->entityManager->persist($locale);
        $this->entityManager->persist($currency);
        $this->entityManager->persist($this->channel);
        $this->entityManager->persist($configuration);
        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }
}
