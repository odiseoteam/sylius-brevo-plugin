<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Entity;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\ToggleableTrait;

class ChannelConfiguration implements ChannelConfigurationInterface
{
    use TimestampableTrait;
    use ToggleableTrait;

    protected ?int $id = null;

    protected ?ChannelInterface $channel = null;

    protected ?string $apiKey = null;

    protected ?string $senderName = null;

    protected ?string $senderEmail = null;

    /** @var list<string> */
    protected array $modules = [];

    protected bool $syncingGuestContacts = true;

    protected bool $deletingContactsOfRemovedCustomers = false;

    protected ?int $customersListId = null;

    protected ?int $newsletterListId = null;

    protected ?int $doubleOptInTemplateId = null;

    protected ?string $trackerClientKey = null;

    public function __construct()
    {
        $this->enabled = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function hasApiKey(): bool
    {
        return null !== $this->apiKey && '' !== $this->apiKey;
    }

    public function getSenderName(): ?string
    {
        return $this->senderName;
    }

    public function setSenderName(?string $senderName): void
    {
        $this->senderName = $senderName;
    }

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(?string $senderEmail): void
    {
        $this->senderEmail = $senderEmail;
    }

    public function getModules(): array
    {
        return $this->modules;
    }

    public function setModules(array $modules): void
    {
        $this->modules = array_values(array_unique($modules));
    }

    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules, true);
    }

    public function isSyncingGuestContacts(): bool
    {
        return $this->syncingGuestContacts;
    }

    public function setSyncingGuestContacts(bool $syncingGuestContacts): void
    {
        $this->syncingGuestContacts = $syncingGuestContacts;
    }

    public function isDeletingContactsOfRemovedCustomers(): bool
    {
        return $this->deletingContactsOfRemovedCustomers;
    }

    public function setDeletingContactsOfRemovedCustomers(bool $deletingContactsOfRemovedCustomers): void
    {
        $this->deletingContactsOfRemovedCustomers = $deletingContactsOfRemovedCustomers;
    }

    public function getCustomersListId(): ?int
    {
        return $this->customersListId;
    }

    public function setCustomersListId(?int $customersListId): void
    {
        $this->customersListId = $customersListId;
    }

    public function getNewsletterListId(): ?int
    {
        return $this->newsletterListId;
    }

    public function setNewsletterListId(?int $newsletterListId): void
    {
        $this->newsletterListId = $newsletterListId;
    }

    public function getDoubleOptInTemplateId(): ?int
    {
        return $this->doubleOptInTemplateId;
    }

    public function setDoubleOptInTemplateId(?int $doubleOptInTemplateId): void
    {
        $this->doubleOptInTemplateId = $doubleOptInTemplateId;
    }

    public function getTrackerClientKey(): ?string
    {
        return $this->trackerClientKey;
    }

    public function setTrackerClientKey(?string $trackerClientKey): void
    {
        $this->trackerClientKey = $trackerClientKey;
    }
}
