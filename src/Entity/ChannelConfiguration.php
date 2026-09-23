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
}
