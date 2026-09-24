<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Api\ContactsApi;
use Odiseo\SyliusBrevoPlugin\Client\Exception\NotFoundException;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactData;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactIdentifier;
use PHPUnit\Framework\TestCase;
use Tests\Odiseo\SyliusBrevoPlugin\Double\BrevoFixture;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;

final class ContactsApiTest extends TestCase
{
    private FakeBrevoHttpClient $client;

    private ContactsApi $api;

    private Credentials $credentials;

    protected function setUp(): void
    {
        $this->client = new FakeBrevoHttpClient();
        $this->api = new ContactsApi($this->client);
        $this->credentials = new Credentials('key');
    }

    public function testItCreatesOrUpdatesAContact(): void
    {
        $this->client->queue('POST', '/contacts', new BrevoResponse(201, ['id' => 21]));

        $id = $this->api->upsert($this->credentials, new ContactData(
            email: 'jane@example.com',
            extId: 'customer-7',
            attributes: ['firstname' => 'Jane', 'LASTNAME' => 'Doe', 'SMS' => null],
            listIds: [2],
        ));

        self::assertSame(21, $id);
        self::assertSame([
            'email' => 'jane@example.com',
            'ext_id' => 'customer-7',
            'attributes' => ['FIRSTNAME' => 'Jane', 'LASTNAME' => 'Doe', 'SMS' => null],
            'listIds' => [2],
            'updateEnabled' => true,
        ], $this->client->lastRequest()?->json);
    }

    public function testUpdatingAnExistingContactReturnsNoId(): void
    {
        $this->client->queue('POST', '/contacts', new BrevoResponse(204));

        self::assertNull($this->api->upsert($this->credentials, new ContactData(email: 'jane@example.com')));
    }

    public function testItUpdatesByExtIdAndChangesTheEmailThroughTheAttribute(): void
    {
        $this->client->queue('PUT', '/contacts/customer-7', new BrevoResponse(204));

        $this->api->update($this->credentials, ContactIdentifier::extId('customer-7'), new ContactData(
            email: 'new@example.com',
            unlinkListIds: [3],
            emailBlacklisted: false,
        ));

        $request = $this->client->lastRequest();
        self::assertSame(['identifierType' => 'ext_id'], $request?->query);
        self::assertSame([
            'attributes' => ['EMAIL' => 'new@example.com'],
            'unlinkListIds' => [3],
            'emailBlacklisted' => false,
        ], $request->json);
    }

    public function testItFindsAContactByAnEncodedEmail(): void
    {
        $this->client->queue('GET', '/contacts/peggy%2Brain%40example.com', BrevoFixture::response('contact'));

        $contact = $this->api->find($this->credentials, ContactIdentifier::email('peggy+rain@example.com'));

        self::assertNotNull($contact);
        self::assertSame(['identifierType' => 'email_id'], $this->client->lastRequest()?->query);
        self::assertSame(42, $contact->id);
        self::assertSame('peggy.rain@example.com', $contact->email);
        self::assertSame('customer-7', $contact->extId);
        self::assertSame('Peggy', $contact->attributes['FIRSTNAME']);
        self::assertTrue($contact->isInList(40));
        self::assertFalse($contact->emailBlacklisted);
        self::assertSame('2017-05-02', $contact->createdAt?->format('Y-m-d'));
    }

    public function testAMissingContactIsNull(): void
    {
        $this->client->queue('GET', '/contacts/nobody%40example.com', BrevoFixture::response('contact_not_found', 404));

        self::assertNull($this->api->find($this->credentials, ContactIdentifier::email('nobody@example.com')));
    }

    public function testItDeletesAContact(): void
    {
        $this->client->queue('DELETE', '/contacts/42', new BrevoResponse(204));
        $this->client->queue('DELETE', '/contacts/43', BrevoFixture::response('contact_not_found', 404));

        self::assertTrue($this->api->delete($this->credentials, ContactIdentifier::contactId(42)));
        self::assertFalse($this->api->delete($this->credentials, ContactIdentifier::contactId(43)));
        self::assertSame(['identifierType' => 'contact_id'], $this->client->lastRequest()?->query);
    }

    public function testUpdatingAMissingContactFails(): void
    {
        $this->client->queue('PUT', '/contacts/customer-9', BrevoFixture::response('contact_not_found', 404));

        $this->expectException(NotFoundException::class);

        $this->api->update($this->credentials, ContactIdentifier::extId('customer-9'), new ContactData(attributes: ['FIRSTNAME' => 'Jo']));
    }
}
