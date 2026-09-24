<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\ArrayReader;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactFolder;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactIdentifierType;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactList;
use Odiseo\SyliusBrevoPlugin\Client\Model\ListMembershipResult;

final class ListsApi implements ListsApiInterface
{
    /** Brevo's max page size for lists and folders. */
    public const PAGE_SIZE = 50;

    /** Brevo's max identifiers per add/remove call. */
    public const MEMBERSHIP_BATCH_SIZE = 150;

    private const IDENTIFIER_FIELDS = [
        'email_id' => 'emails',
        'ext_id' => 'extIds',
        'contact_id' => 'ids',
    ];

    public function __construct(private readonly BrevoHttpClientInterface $client)
    {
    }

    public function lists(Credentials $credentials): iterable
    {
        foreach ($this->paginate($credentials, '/contacts/lists', 'lists') as $list) {
            yield ContactList::fromArray($list);
        }
    }

    public function createList(Credentials $credentials, string $name, int $folderId): int
    {
        $data = $this->client->request($credentials, 'POST', '/contacts/lists', json: ['name' => $name, 'folderId' => $folderId])->data;

        return ArrayReader::int($data, 'id') ?? 0;
    }

    public function addContacts(Credentials $credentials, int $listId, ContactIdentifierType $type, array $identifiers): ListMembershipResult
    {
        return $this->changeMembership($credentials, sprintf('/contacts/lists/%d/contacts/add', $listId), $type, $identifiers);
    }

    public function removeContacts(Credentials $credentials, int $listId, ContactIdentifierType $type, array $identifiers): ListMembershipResult
    {
        return $this->changeMembership($credentials, sprintf('/contacts/lists/%d/contacts/remove', $listId), $type, $identifiers);
    }

    public function folders(Credentials $credentials): iterable
    {
        foreach ($this->paginate($credentials, '/contacts/folders', 'folders') as $folder) {
            yield ContactFolder::fromArray($folder);
        }
    }

    public function createFolder(Credentials $credentials, string $name): int
    {
        $data = $this->client->request($credentials, 'POST', '/contacts/folders', json: ['name' => $name])->data;

        return ArrayReader::int($data, 'id') ?? 0;
    }

    /** @param list<string|int> $identifiers */
    private function changeMembership(Credentials $credentials, string $path, ContactIdentifierType $type, array $identifiers): ListMembershipResult
    {
        $field = self::IDENTIFIER_FIELDS[$type->value] ?? throw new \InvalidArgumentException(sprintf('Lists take emails, ext_ids or contact ids, not "%s".', $type->value));

        $result = new ListMembershipResult();
        foreach (array_chunk($identifiers, self::MEMBERSHIP_BATCH_SIZE) as $batch) {
            $data = $this->client->request($credentials, 'POST', $path, json: [$field => $batch])->data;
            $result = $result->merge(ListMembershipResult::fromArray($data));
        }

        return $result;
    }

    /** @return \Generator<array<array-key, mixed>> */
    private function paginate(Credentials $credentials, string $path, string $key): \Generator
    {
        $offset = 0;
        do {
            $data = $this->client->request($credentials, 'GET', $path, ['limit' => self::PAGE_SIZE, 'offset' => $offset, 'sort' => 'asc'])->data;
            $items = ArrayReader::arrays($data, $key);

            foreach ($items as $item) {
                yield $item;
            }

            $offset += self::PAGE_SIZE;
            $total = ArrayReader::int($data, 'count');
        } while (count($items) === self::PAGE_SIZE && (null === $total || $offset < $total));
    }
}
