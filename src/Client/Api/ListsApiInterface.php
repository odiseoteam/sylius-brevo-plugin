<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactFolder;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactIdentifierType;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactList;
use Odiseo\SyliusBrevoPlugin\Client\Model\ListMembershipResult;

interface ListsApiInterface
{
    /**
     * Every list, page after page.
     *
     * @return iterable<ContactList>
     *
     * @throws BrevoException
     */
    public function lists(Credentials $credentials): iterable;

    /**
     * @return int the new list id
     *
     * @throws BrevoException
     */
    public function createList(Credentials $credentials, string $name, int $folderId): int;

    /**
     * Existing contacts only; unknown ones come back as failures.
     *
     * @param list<string|int> $identifiers all of the given type (email, ext_id or contact id)
     *
     * @throws BrevoException
     */
    public function addContacts(Credentials $credentials, int $listId, ContactIdentifierType $type, array $identifiers): ListMembershipResult;

    /**
     * @param list<string|int> $identifiers all of the given type (email, ext_id or contact id)
     *
     * @throws BrevoException
     */
    public function removeContacts(Credentials $credentials, int $listId, ContactIdentifierType $type, array $identifiers): ListMembershipResult;

    /**
     * Every folder, page after page.
     *
     * @return iterable<ContactFolder>
     *
     * @throws BrevoException
     */
    public function folders(Credentials $credentials): iterable;

    /**
     * @return int the new folder id
     *
     * @throws BrevoException
     */
    public function createFolder(Credentials $credentials, string $name): int;
}
