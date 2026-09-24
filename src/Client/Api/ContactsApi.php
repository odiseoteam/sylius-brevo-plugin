<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Exception\NotFoundException;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\ArrayReader;
use Odiseo\SyliusBrevoPlugin\Client\Model\Contact;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactData;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactIdentifier;

final class ContactsApi implements ContactsApiInterface
{
    public function __construct(private readonly BrevoHttpClientInterface $client)
    {
    }

    public function upsert(Credentials $credentials, ContactData $data): ?int
    {
        $response = $this->client->request($credentials, 'POST', '/contacts', json: [
            ...$data->toCreatePayload(),
            'updateEnabled' => true,
        ]);

        // 201 with the new id; 204 without body when it updated an existing one.
        return ArrayReader::int($response->data, 'id');
    }

    public function update(Credentials $credentials, ContactIdentifier $identifier, ContactData $data): void
    {
        $this->client->request($credentials, 'PUT', $identifier->path(), $identifier->query(), $data->toUpdatePayload());
    }

    public function find(Credentials $credentials, ContactIdentifier $identifier): ?Contact
    {
        try {
            return Contact::fromArray($this->client->request($credentials, 'GET', $identifier->path(), $identifier->query())->data);
        } catch (NotFoundException) {
            return null;
        }
    }

    public function delete(Credentials $credentials, ContactIdentifier $identifier): bool
    {
        try {
            $this->client->request($credentials, 'DELETE', $identifier->path(), $identifier->query());
        } catch (NotFoundException) {
            return false;
        }

        return true;
    }
}
