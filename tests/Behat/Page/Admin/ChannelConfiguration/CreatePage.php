<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Page\Admin\ChannelConfiguration;

use Sylius\Behat\Page\Admin\Crud\CreatePage as BaseCreatePage;

final class CreatePage extends BaseCreatePage implements CreatePageInterface
{
    use ConfigurationFormTrait;

    public function chooseChannel(string $name): void
    {
        $this->getElement('channel')->selectOption($name);
    }
}
