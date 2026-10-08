<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Configuration;

use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Contact\ContactsModule;
use Odiseo\SyliusBrevoPlugin\Newsletter\NewsletterModule;

/** Resolved, ready-to-use Brevo settings of an enabled channel. */
final readonly class BrevoSettings
{
    /** @param list<string> $modules */
    public function __construct(
        public string $channelCode,
        public Credentials $credentials,
        public ?string $senderName = null,
        public ?string $senderEmail = null,
        public array $modules = [],
        public bool $syncingGuestContacts = true,
        public bool $deletingContactsOfRemovedCustomers = false,
        public ?int $customersListId = null,
        public ?int $newsletterListId = null,
        public ?int $doubleOptInTemplateId = null,
        public ?string $trackerClientKey = null,
    ) {
    }

    public function hasNewsletter(): bool
    {
        return null !== $this->newsletterListId && $this->hasModule(ContactsModule::CODE) && $this->hasModule(NewsletterModule::CODE);
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules, true);
    }
}
