<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\Contact;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactData;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactIdentifier;

interface ContactsApiInterface
{
    /**
     * Creates the contact or updates the existing one (matched by email or ext_id).
     *
     * @return int|null the id when created, null when an existing contact was updated
     *
     * @throws BrevoException
     */
    public function upsert(Credentials $credentials, ContactData $data): ?int;

    /** @throws BrevoException also when the contact doesn't exist */
    public function update(Credentials $credentials, ContactIdentifier $identifier, ContactData $data): void;

    /** @throws BrevoException */
    public function find(Credentials $credentials, ContactIdentifier $identifier): ?Contact;

    /**
     * Bulk create or update, processed by Brevo in the background.
     *
     * @param list<ContactData> $contacts
     * @param non-empty-list<int> $listIds lists every imported contact joins (Brevo requires one)
     *
     * @return int the process id, see ProcessesApiInterface
     *
     * @throws BrevoException
     */
    public function import(Credentials $credentials, array $contacts, array $listIds): int;

    /**
     * @return bool false when there was no such contact
     *
     * @throws BrevoException
     */
    public function delete(Credentials $credentials, ContactIdentifier $identifier): bool;
}
