<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Page\Admin\ChannelConfiguration;

use Sylius\Behat\Page\Admin\Crud\UpdatePageInterface as BaseUpdatePageInterface;

interface UpdatePageInterface extends BaseUpdatePageInterface
{
    public function fillApiKey(string $apiKey): void;

    public function getApiKey(): string;

    public function fillSender(string $name, string $email): void;

    public function enableModule(string $label): void;

    public function isChannelDisabled(): bool;

    public function testConnection(): void;
}
