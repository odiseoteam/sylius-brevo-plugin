<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;

/** A channel of its own host with the newsletter on (list 12), inside a rolled back transaction. */
trait NewsletterChannelTrait
{
    private const HOST = 'newsletter.brevo.test';

    private KernelBrowser $browser;

    private EntityManagerInterface $entityManager;

    private FakeBrevoHttpClient $client;

    private ChannelConfiguration $configuration;

    protected function setUp(): void
    {
        $this->browser = self::createClient(server: ['HTTP_HOST' => self::HOST]);
        $this->browser->disableReboot();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $this->entityManager->getConnection()->beginTransaction();
        $this->entityManager->getConnection()->executeStatement('DELETE FROM odiseo_brevo_channel_configuration');

        $client = self::getContainer()->get('odiseo_brevo.client.http.transport');
        self::assertInstanceOf(FakeBrevoHttpClient::class, $client);
        $this->client = $client;
        $this->client->reset();

        $this->configureChannel();
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();

        parent::tearDown();
    }

    private function customer(string $email): ?Customer
    {
        return $this->entityManager->getRepository(Customer::class)->findOneBy(['emailCanonical' => $email]);
    }

    private function configureChannel(): void
    {
        $locale = $this->entityManager->getRepository(Locale::class)->findOneBy(['code' => 'en_US']) ?? new Locale();
        $locale->setCode('en_US');
        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']) ?? new Currency();
        $currency->setCode('USD');

        $channel = new Channel();
        $channel->setCode('BREVO_NEWSLETTER');
        $channel->setName('Brevo newsletter');
        $channel->setHostname(self::HOST);
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setDefaultLocale($locale);
        $channel->addLocale($locale);
        $channel->setBaseCurrency($currency);
        $channel->addCurrency($currency);

        $this->configuration = new ChannelConfiguration();
        $this->configuration->setChannel($channel);
        $this->configuration->setApiKey('xkeysib-test');
        $this->configuration->setModules(['contacts', 'newsletter']);
        $this->configuration->setNewsletterListId(12);

        $this->entityManager->persist($locale);
        $this->entityManager->persist($currency);
        $this->entityManager->persist($channel);
        $this->entityManager->persist($this->configuration);
        $this->entityManager->flush();
    }
}
