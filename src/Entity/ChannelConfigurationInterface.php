<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Entity;

use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Component\Payment\Encryption\EncryptionAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

/** Brevo settings of one channel. The API key is encrypted at rest and plain once loaded. */
interface ChannelConfigurationInterface extends ResourceInterface, ChannelAwareInterface, ToggleableInterface, TimestampableInterface, EncryptionAwareInterface
{
    public function getApiKey(): ?string;

    public function setApiKey(?string $apiKey): void;

    public function hasApiKey(): bool;

    public function getSenderName(): ?string;

    public function setSenderName(?string $senderName): void;

    public function getSenderEmail(): ?string;

    public function setSenderEmail(?string $senderEmail): void;

    /** @return list<string> */
    public function getModules(): array;

    /** @param array<array-key, string> $modules */
    public function setModules(array $modules): void;

    public function hasModule(string $module): bool;

    /** Customers without an account (guest checkouts) become contacts too. */
    public function isSyncingGuestContacts(): bool;

    public function setSyncingGuestContacts(bool $syncingGuestContacts): void;

    /** Deleting a customer deletes its contact; otherwise the contact stays, unsynced. */
    public function isDeletingContactsOfRemovedCustomers(): bool;

    public function setDeletingContactsOfRemovedCustomers(bool $deletingContactsOfRemovedCustomers): void;

    /** Brevo list every synced customer joins (also the target of the initial import). */
    public function getCustomersListId(): ?int;

    public function setCustomersListId(?int $customersListId): void;

    /** Brevo list of the newsletter subscribers; null turns the newsletter off. */
    public function getNewsletterListId(): ?int;

    public function setNewsletterListId(?int $newsletterListId): void;

    /** Brevo double opt-in template for shop subscriptions; null subscribes right away. */
    public function getDoubleOptInTemplateId(): ?int;

    public function setDoubleOptInTemplateId(?int $doubleOptInTemplateId): void;

    /** The Brevo tracker's client key (Automation > Settings), public in the shop pages. */
    public function getTrackerClientKey(): ?string;

    public function setTrackerClientKey(?string $trackerClientKey): void;
}
