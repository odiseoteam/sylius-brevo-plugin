<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Integration\Catalog;

use Doctrine\ORM\EntityManagerInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/** Product changes and the products command, outside a request: they reach Brevo right after the flush. */
final class ProductTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private FakeBrevoHttpClient $client;

    private Product $product;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $this->entityManager->getConnection()->beginTransaction();
        $this->entityManager->getConnection()->executeStatement('DELETE FROM odiseo_brevo_channel_configuration');

        $client = self::getContainer()->get('odiseo_brevo.client.http.transport');
        self::assertInstanceOf(FakeBrevoHttpClient::class, $client);
        $this->client = $client;

        $this->configureCatalog();
        $this->client->reset();
        $this->client->respondAlways('POST', '/products/batch', new BrevoResponse(201, ['createdCount' => 0, 'updatedCount' => 1]));
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();

        parent::tearDown();
    }

    public function testChangingAPriceUpdatesThatVariant(): void
    {
        $this->pricing('brevo_tee_m')->setPrice(1990);
        $this->entityManager->flush();

        $products = $this->sentProducts();
        self::assertSame([['brevo_tee_m', 'brevo_tee', 19.9, false]], array_map(
            static fn (array $product): array => [$product['id'] ?? null, $product['parentId'] ?? null, $product['price'] ?? null, $product['isDeleted'] ?? null],
            $products,
        ));
        self::assertSame('Brevo tee', $products[0]['name'] ?? null);
    }

    public function testAChangeThatDoesntReachThePayloadIsNotSentAgain(): void
    {
        $this->variant('brevo_tee_m')->setShippingRequired(false);
        $this->entityManager->flush();

        self::assertSame([], $this->client->requests('POST', '/products/batch'));
    }

    public function testDisablingAProductDeletesItsVariants(): void
    {
        $this->product->setEnabled(false);
        $this->entityManager->flush();

        self::assertEqualsCanonicalizing(['brevo_tee_m' => true, 'brevo_tee_l' => true], array_column($this->sentProducts(), 'isDeleted', 'id'));
    }

    public function testDeletingAVariantDeletesItsProduct(): void
    {
        $variant = $this->variant('brevo_tee_l');
        $this->product->removeVariant($variant);
        $this->entityManager->remove($variant);
        $this->entityManager->flush();

        self::assertSame([['id' => 'brevo_tee_l', 'name' => 'Brevo tee', 'isDeleted' => true]], $this->sentProducts());
    }

    public function testTheSyncCommandSendsWhatChangedAndResumes(): void
    {
        $tester = $this->command('odiseo:brevo:products:sync');

        $tester->execute(['--channel' => 'BREVO_TEST', '--dry-run' => true, '--force' => true]);
        self::assertSame([], $this->client->requests());
        self::assertStringContainsString('brevo_tee_l', $tester->getDisplay());

        $tester->execute(['--channel' => 'BREVO_TEST', '--force' => true]);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(['brevo_tee_m', 'brevo_tee_l'], array_column($this->sentProducts(), 'id'));

        $this->client->reset();
        $this->client->respondAlways('POST', '/products/batch', new BrevoResponse(201, []));
        $tester->execute(['--channel' => 'BREVO_TEST']);
        self::assertSame([], $this->client->requests());
        self::assertStringContainsString('2 unchanged', $tester->getDisplay());

        $firstId = $this->variant('brevo_tee_m')->getId();
        self::assertIsInt($firstId);
        $tester->execute(['--channel' => 'BREVO_TEST', '--force' => true, '--after-id' => (string) $firstId]);
        self::assertSame(['brevo_tee_l'], array_column($this->sentProducts(), 'id'));
    }

    /** @return list<array<string, mixed>> */
    private function sentProducts(): array
    {
        $products = [];
        foreach ($this->client->requests('POST', '/products/batch') as $request) {
            /** @var list<array<string, mixed>> $batch */
            $batch = $request->json['products'] ?? [];
            $products = [...$products, ...$batch];
        }

        return $products;
    }

    private function command(string $name): CommandTester
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        return new CommandTester((new Application($kernel))->find($name));
    }

    private function variant(string $code): ProductVariant
    {
        $variant = $this->entityManager->getRepository(ProductVariant::class)->findOneBy(['code' => $code]);
        self::assertInstanceOf(ProductVariant::class, $variant);

        return $variant;
    }

    private function pricing(string $variantCode): ChannelPricing
    {
        $pricing = $this->variant($variantCode)->getChannelPricings()->first();
        self::assertInstanceOf(ChannelPricing::class, $pricing);

        return $pricing;
    }

    private function configureCatalog(): void
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

        $channel = new Channel();
        $channel->setCode('BREVO_TEST');
        $channel->setName('Brevo test');
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setDefaultLocale($locale);
        $channel->setBaseCurrency($currency);

        $configuration = new ChannelConfiguration();
        $configuration->setChannel($channel);
        $configuration->setApiKey('xkeysib-test');
        $configuration->setModules(['catalog']);

        $this->product = new Product();
        $this->product->setCurrentLocale('en_US');
        $this->product->setFallbackLocale('en_US');
        $this->product->setCode('brevo_tee');
        $this->product->setName('Brevo tee');
        $this->product->setSlug('brevo-tee');
        $this->product->addChannel($channel);
        foreach (['brevo_tee_m' => 2500, 'brevo_tee_l' => 2700] as $code => $price) {
            $variant = new ProductVariant();
            $variant->setCurrentLocale('en_US');
            $variant->setFallbackLocale('en_US');
            $variant->setCode($code);
            $pricing = new ChannelPricing();
            $pricing->setChannelCode('BREVO_TEST');
            $pricing->setPrice($price);
            $variant->addChannelPricing($pricing);
            $this->product->addVariant($variant);
        }

        $this->entityManager->persist($locale);
        $this->entityManager->persist($currency);
        $this->entityManager->persist($channel);
        $this->entityManager->persist($configuration);
        $this->entityManager->persist($this->product);
        $this->entityManager->flush();
    }
}
