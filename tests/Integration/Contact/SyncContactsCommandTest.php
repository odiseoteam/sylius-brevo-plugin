<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Integration\Contact;

use Doctrine\ORM\EntityManagerInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Contact\ContactExtId;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;
use Tests\Odiseo\SyliusBrevoPlugin\Double\RecordedRequest;

final class SyncContactsCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private FakeBrevoHttpClient $client;

    private ChannelConfiguration $configuration;

    /** Leaves out customers other tests left behind. */
    private string $since;

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

        // Before any customer: the live sync caches the account attributes too.
        $this->client->reset();
        $this->client->respondAlways('GET', '/contacts/attributes', new BrevoResponse(200, ['attributes' => [['name' => 'FIRSTNAME', 'category' => 'normal']]]));
        $this->client->respondAlways('POST', '/contacts/import', new BrevoResponse(202, ['processId' => 78]));
        $this->client->respondAlways('GET', '/processes/78', new BrevoResponse(200, ['id' => 78, 'status' => 'completed']));

        $this->configureChannel();
        $this->since = (new \DateTimeImmutable('-1 second'))->format('Y-m-d H:i:s');
        $this->customer('carrot@example.com', 'Carrot', registered: true);
        $this->customer('nobby@example.com', 'Nobby', registered: false);
        $this->customer('angua@example.com', 'Angua', registered: true);
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();

        parent::tearDown();
    }

    public function testItImportsEveryCustomerInBatchesIntoTheCustomersList(): void
    {
        $tester = $this->runCommand(['--batch-size' => '2']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $imports = $this->imports();
        self::assertCount(2, $imports);
        self::assertSame([12], $imports[0]->json['listIds'] ?? null);
        self::assertSame(['carrot@example.com', 'nobby@example.com', 'angua@example.com'], $this->importedEmails());
        self::assertSame(
            ['email' => 'carrot@example.com', 'attributes' => ['FIRSTNAME' => 'Carrot', 'EXT_ID' => $this->idOf('carrot@example.com')]],
            ((array) ($imports[0]->json['jsonBody'] ?? []))[0] ?? null,
        );
        self::assertStringContainsString('Every import completed', $tester->getDisplay());
    }

    public function testGuestsStayOutWhenTheChannelDoesNotSyncThem(): void
    {
        $this->configuration()->setSyncingGuestContacts(false);
        $this->entityManager->flush();

        $this->runCommand();

        self::assertSame(['carrot@example.com', 'angua@example.com'], $this->importedEmails());
    }

    public function testTheListCanBeGivenAndIsRequired(): void
    {
        self::assertSame(Command::SUCCESS, $this->runCommand(['--list' => '40', '--no-wait' => true])->getStatusCode());
        self::assertSame([40], $this->imports()[0]->json['listIds'] ?? null);
        self::assertSame([], $this->client->requests('GET', '/processes/78'));

        $this->configuration()->setCustomersListId(null);
        $this->entityManager->flush();

        $tester = $this->runCommand();
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No list to import into', $tester->getDisplay());
    }

    public function testADryRunSendsNothing(): void
    {
        $tester = $this->runCommand(['--dry-run' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame([], $this->imports());
        self::assertStringContainsString('3 customers into list #12', $tester->getDisplay());
    }

    public function testAFailedImportFailsTheCommand(): void
    {
        $this->client->queue('GET', '/processes/78', new BrevoResponse(200, ['id' => 78, 'status' => 'failed']));

        self::assertSame(Command::FAILURE, $this->runCommand()->getStatusCode());
    }

    /** @param array<string, mixed> $options */
    private function runCommand(array $options = []): CommandTester
    {
        $application = new Application(self::$kernel ?? throw new \LogicException('No kernel.'));
        $tester = new CommandTester($application->find('odiseo:brevo:contacts:sync'));
        $tester->execute(['--since' => $this->since, ...$options]);

        return $tester;
    }

    /** The command clears the entity manager between batches. */
    private function configuration(): ChannelConfiguration
    {
        $configuration = $this->entityManager->find(ChannelConfiguration::class, $this->configuration->getId());
        self::assertInstanceOf(ChannelConfiguration::class, $configuration);

        return $configuration;
    }

    /** @return list<RecordedRequest> */
    private function imports(): array
    {
        return $this->client->requests('POST', '/contacts/import');
    }

    /** @return list<mixed> */
    private function importedEmails(): array
    {
        $emails = [];
        foreach ($this->imports() as $import) {
            foreach ((array) ($import->json['jsonBody'] ?? []) as $item) {
                $emails[] = is_array($item) ? ($item['email'] ?? null) : null;
            }
        }

        return $emails;
    }

    private function idOf(string $email): string
    {
        $customer = $this->entityManager->getRepository(Customer::class)->findOneBy(['emailCanonical' => $email]);
        self::assertNotNull($customer);

        return ContactExtId::of($customer);
    }

    private function customer(string $email, string $firstName, bool $registered): void
    {
        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setEmailCanonical($email);
        $customer->setFirstName($firstName);
        $this->entityManager->persist($customer);

        if ($registered) {
            $user = new ShopUser();
            $user->setCustomer($customer);
            $user->setUsername($email);
            $user->setUsernameCanonical($email);
            $user->setPassword('secret');
            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
    }

    private function configureChannel(): void
    {
        $locale = $this->entityManager->getRepository(Locale::class)->findOneBy(['code' => 'en_US']) ?? new Locale();
        $locale->setCode('en_US');
        $currency = $this->entityManager->getRepository(Currency::class)->findOneBy(['code' => 'USD']) ?? new Currency();
        $currency->setCode('USD');

        $channel = new Channel();
        $channel->setCode('BREVO_IMPORT');
        $channel->setName('Brevo import');
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setDefaultLocale($locale);
        $channel->setBaseCurrency($currency);

        $this->configuration = new ChannelConfiguration();
        $this->configuration->setChannel($channel);
        $this->configuration->setApiKey('xkeysib-test');
        $this->configuration->setModules(['contacts']);
        $this->configuration->setCustomersListId(12);

        $this->entityManager->persist($locale);
        $this->entityManager->persist($currency);
        $this->entityManager->persist($channel);
        $this->entityManager->persist($this->configuration);
        $this->entityManager->flush();
    }
}
