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
}
