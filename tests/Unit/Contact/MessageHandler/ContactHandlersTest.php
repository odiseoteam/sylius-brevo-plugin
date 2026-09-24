<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Contact\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Client\Api\ContactsApi;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactData;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\AccountAttributesInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\Message\DeleteContact;
use Odiseo\SyliusBrevoPlugin\Contact\Message\SyncContact;
use Odiseo\SyliusBrevoPlugin\Contact\MessageHandler\DeleteContactHandler;
use Odiseo\SyliusBrevoPlugin\Contact\MessageHandler\SyncContactHandler;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Tests\Odiseo\SyliusBrevoPlugin\Double\BrevoFixture;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;
use Tests\Odiseo\SyliusBrevoPlugin\Double\InMemoryLogger;

final class ContactHandlersTest extends TestCase
{
    private FakeBrevoHttpClient $client;

    private InMemoryLogger $logger;

    private BrevoSettings $settings;

    protected function setUp(): void
    {
        $this->client = new FakeBrevoHttpClient();
        $this->logger = new InMemoryLogger();
        $this->settings = new BrevoSettings('WEB', new Credentials('key'), modules: ['contacts'], deletingContactsOfRemovedCustomers: true);
    }

    public function testItUpdatesTheContactByExtId(): void
    {
        $this->client->queue('PUT', '/contacts/7', new BrevoResponse(204));

        ($this->syncHandler())(new SyncContact('WEB', 7));

        $request = $this->client->lastRequest();
        self::assertSame(['PUT', '/contacts/7', ['identifierType' => 'ext_id']], [$request?->method, $request?->path, $request?->query]);
        self::assertSame(['FIRSTNAME' => 'Carrot', 'EMAIL' => 'carrot@example.com'], $request?->json['attributes'] ?? null);
        self::assertCount(1, $this->client->requests());
    }

    public function testItCreatesTheContactWhenBrevoDoesNotKnowItsExtId(): void
    {
        $this->client->queue('PUT', '/contacts/7', BrevoFixture::response('contact_not_found', 404));
        $this->client->queue('POST', '/contacts', new BrevoResponse(201, ['id' => 21]));

        ($this->syncHandler())(new SyncContact('WEB', 7));

        self::assertSame([
            'email' => 'carrot@example.com',
            'ext_id' => '7',
            'attributes' => ['FIRSTNAME' => 'Carrot'],
            'updateEnabled' => true,
        ], $this->client->lastRequest()?->json);
    }

    public function testItLeavesOutAttributesTheAccountLacks(): void
    {
        ($this->syncHandler())(new SyncContact('WEB', 7));

        self::assertArrayNotHasKey('TOTAL_SPENT', (array) ($this->client->lastRequest()?->json['attributes'] ?? []));
        self::assertSame('warning', $this->logger->records[0]['level']);
        self::assertSame(['TOTAL_SPENT'], $this->logger->records[0]['context']['attributes']);
    }

    public function testNothingIsSentWithoutTheModule(): void
    {
        $this->settings = new BrevoSettings('WEB', new Credentials('key'));

        ($this->syncHandler())(new SyncContact('WEB', 7));
        ($this->deleteHandler())(new DeleteContact('WEB', '7', 'carrot@example.com'));

        self::assertSame([], $this->client->requests());
    }

    public function testItDeletesByExtIdAndFallsBackToTheEmail(): void
    {
        $this->client->queue('DELETE', '/contacts/7', BrevoFixture::response('contact_not_found', 404));
        $this->client->queue('DELETE', '/contacts/carrot%40example.com', new BrevoResponse(204));

        ($this->deleteHandler())(new DeleteContact('WEB', '7', 'carrot@example.com'));

        self::assertCount(2, $this->client->requests('DELETE'));
        self::assertSame(['identifierType' => 'email_id'], $this->client->lastRequest()?->query);
    }

    public function testContactsStayWhenDeletingIsOff(): void
    {
        $this->settings = new BrevoSettings('WEB', new Credentials('key'), modules: ['contacts']);

        ($this->deleteHandler())(new DeleteContact('WEB', '7', 'carrot@example.com'));

        self::assertSame([], $this->client->requests());
    }

    private function syncHandler(): SyncContactHandler
    {
        $customer = new Customer();
        $customer->setEmail('carrot@example.com');

        $customerRepository = $this->createStub(RepositoryInterface::class);
        $customerRepository->method('find')->willReturn($customer);

        $builder = $this->createStub(ContactPayloadBuilderInterface::class);
        $builder->method('build')->willReturn(new ContactData(
            email: 'carrot@example.com',
            extId: '7',
            attributes: ['FIRSTNAME' => 'Carrot', 'TOTAL_SPENT' => 10.0],
        ));

        $accountAttributes = $this->createStub(AccountAttributesInterface::class);
        $accountAttributes->method('names')->willReturn(['FIRSTNAME', 'LASTNAME']);

        return new SyncContactHandler(
            $customerRepository,
            $this->channelRepository(),
            $this->configurationProvider(),
            $builder,
            $accountAttributes,
            new ContactsApi($this->client),
            $this->logger,
        );
    }

    private function deleteHandler(): DeleteContactHandler
    {
        return new DeleteContactHandler($this->channelRepository(), $this->configurationProvider(), new ContactsApi($this->client));
    }

    /** @return ChannelRepositoryInterface<ChannelInterface> */
    private function channelRepository(): ChannelRepositoryInterface
    {
        $repository = $this->createStub(ChannelRepositoryInterface::class);
        $repository->method('findOneByCode')->willReturn(new Channel());

        return $repository;
    }

    private function configurationProvider(): ConfigurationProviderInterface
    {
        $provider = $this->createStub(ConfigurationProviderInterface::class);
        $provider->method('getSettings')->willReturnCallback(fn (): BrevoSettings => $this->settings);

        return $provider;
    }
}
