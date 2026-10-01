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

    public function chooseCustomersList(string $name): void;

    public function chooseNewsletterList(string $name): void;

    public function getModulesValidationMessage(): string;

    public function fillDoubleOptInTemplate(int $templateId): void;

    public function isChannelDisabled(): bool;

    public function testConnection(): void;

    /** @return list<string> */
    public function getMissingExchangeRates(): array;
}
