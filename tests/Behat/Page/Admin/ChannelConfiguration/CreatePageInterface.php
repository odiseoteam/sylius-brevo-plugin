<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Page\Admin\ChannelConfiguration;

use Sylius\Behat\Page\Admin\Crud\CreatePageInterface as BaseCreatePageInterface;

interface CreatePageInterface extends BaseCreatePageInterface
{
    public function chooseChannel(string $name): void;

    public function fillApiKey(string $apiKey): void;

    public function fillSender(string $name, string $email): void;

    public function enableModule(string $label): void;
}
