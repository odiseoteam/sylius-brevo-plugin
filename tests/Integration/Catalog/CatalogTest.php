<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Integration\Catalog;

use Doctrine\ORM\EntityManagerInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Odiseo\SyliusBrevoPlugin\Testing\FakeBrevoHttpClient;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/** Taxon changes and the catalog commands, outside a request: they reach Brevo right after the flush. */
final class CatalogTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private FakeBrevoHttpClient $client;

    private Taxon $menu;

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

        $this->configureChannel();
        $this->client->reset();
        $this->client->respondAlways('POST', '/categories/batch', new BrevoResponse(201, ['createdCount' => 0, 'updatedCount' => 1]));
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();

        parent::tearDown();
    }

    public function testRenamingATaxonUpdatesItsCategory(): void
    {
        $shirts = $this->taxon('brevo_shirts');
        $shirts->setName('Shirts');
        $this->entityManager->flush();

        $categories = $this->sentCategories();
        self::assertCount(1, $categories);
        self::assertSame(['brevo_shirts', 'Shirts', false], [$categories[0]['id'], $categories[0]['name'], $categories[0]['isDeleted']]);
        self::assertIsString($categories[0]['url'] ?? null);
        self::assertStringEndsWith('/en_US/taxons/brevo-menu/brevo-shirts', $categories[0]['url']);
    }

    public function testRenamingATaxonRenamesItsChildren(): void
    {
        $this->taxon('brevo_caps')->setName('Hats');
        $this->entityManager->flush();

        self::assertSame(['brevo_caps' => 'Hats', 'brevo_beanies' => 'Hats > Beanies'], array_column($this->sentCategories(), 'name', 'id'));
    }

    public function testMovingATaxonOutOfTheMenuDeletesItsCategoryAndItsChildren(): void
    {
        $other = $this->newTaxon('brevo_other', 'Other');
        $this->entityManager->persist($other);
        $this->entityManager->flush();
        $this->client->reset();
        $this->client->respondAlways('POST', '/categories/batch', new BrevoResponse(201, []));

        $caps = $this->taxon('brevo_caps');
        $caps->setParent($other);
        $this->entityManager->flush();

        $categories = $this->sentCategories();
        self::assertSame(['brevo_caps' => true, 'brevo_beanies' => true], array_column($categories, 'isDeleted', 'id'));
    }

    public function testDeletingATaxonDeletesItsCategory(): void
    {
        $shirts = $this->taxon('brevo_shirts');
        $this->menu->removeChild($shirts);
        $this->entityManager->remove($shirts);
        $this->entityManager->flush();

        self::assertSame([['id' => 'brevo_shirts', 'name' => 'Shirts & tees', 'isDeleted' => true]], $this->sentCategories());
    }

    public function testTheSyncCommandSendsEveryTaxonOfTheChannelMenu(): void
    {
        $tester = $this->command('odiseo:brevo:categories:sync');

        $tester->execute(['--channel' => 'BREVO_TEST', '--dry-run' => true]);
        self::assertSame([], $this->client->requests());
        self::assertStringContainsString('brevo_beanies', $tester->getDisplay());

        $tester->execute(['--channel' => 'BREVO_TEST']);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(['brevo_shirts', 'brevo_caps', 'brevo_beanies'], array_column($this->sentCategories(), 'id'));
    }

    public function testTheActivateCommandActivatesEcommerceInTheBaseCurrency(): void
    {
        $this->client->queue('GET', '/ecommerce/config/displayCurrency', new BrevoResponse(403, ['message' => 'Forbidden']));
        $tester = $this->command('odiseo:brevo:ecommerce:activate');

        $tester->execute(['--channel' => 'BREVO_TEST']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertCount(1, $this->client->requests('POST', '/ecommerce/activate'));
        self::assertSame(['code' => 'USD'], $this->client->lastRequest()?->json);
    }

    public function testAnActiveAccountIsNotActivatedAgain(): void
    {
        $this->client->queue('GET', '/ecommerce/config/displayCurrency', new BrevoResponse(200, ['code' => 'USD']));

        $this->command('odiseo:brevo:ecommerce:activate')->execute([]);

        self::assertSame([], $this->client->requests('POST'));
    }

    /** @return list<array<string, mixed>> */
    private function sentCategories(): array
    {
        $categories = [];
        foreach ($this->client->requests('POST', '/categories/batch') as $request) {
            /** @var list<array<string, mixed>> $batch */
            $batch = $request->json['categories'] ?? [];
            $categories = [...$categories, ...$batch];
        }

        return $categories;
    }

    private function command(string $name): CommandTester
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);

        return new CommandTester((new Application($kernel))->find($name));
    }

    private function taxon(string $code): Taxon
    {
        $taxon = $this->entityManager->getRepository(Taxon::class)->findOneBy(['code' => $code]);
        self::assertInstanceOf(Taxon::class, $taxon);

        return $taxon;
    }

    private function newTaxon(string $code, string $name, ?Taxon $parent = null): Taxon
    {
        $taxon = new Taxon();
        $taxon->setCurrentLocale('en_US');
        $taxon->setFallbackLocale('en_US');
        $taxon->setCode($code);
        $taxon->setName($name);
        $taxon->setSlug(str_replace('_', '-', (null === $parent ? '' : $parent->getSlug() . '/') . $code));
        $parent?->addChild($taxon);

        return $taxon;
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

        $this->menu = $this->newTaxon('brevo_menu', 'Menu');
        $this->newTaxon('brevo_shirts', 'Shirts & tees', $this->menu);
        $caps = $this->newTaxon('brevo_caps', 'Caps', $this->menu);
        $this->newTaxon('brevo_beanies', 'Beanies', $caps);

        $channel = new Channel();
        $channel->setCode('BREVO_TEST');
        $channel->setName('Brevo test');
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setDefaultLocale($locale);
        $channel->setBaseCurrency($currency);
        $channel->setMenuTaxon($this->menu);

        $configuration = new ChannelConfiguration();
        $configuration->setChannel($channel);
        $configuration->setApiKey('xkeysib-test');
        $configuration->setModules(['catalog']);

        $this->entityManager->persist($locale);
        $this->entityManager->persist($currency);
        $this->entityManager->persist($this->menu);
        $this->entityManager->persist($channel);
        $this->entityManager->persist($configuration);
        $this->entityManager->flush();
    }
}
