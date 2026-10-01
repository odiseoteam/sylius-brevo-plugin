<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Page\Admin\ChannelConfiguration;

use Sylius\Behat\Page\Admin\Crud\UpdatePage as BaseUpdatePage;

final class UpdatePage extends BaseUpdatePage implements UpdatePageInterface
{
    use ConfigurationFormTrait {
        getDefinedElements as getFormElements;
    }

    public function isChannelDisabled(): bool
    {
        return $this->getElement('channel')->hasAttribute('disabled');
    }

    public function testConnection(): void
    {
        $this->getElement('test_connection')->press();
    }

    public function getMissingExchangeRates(): array
    {
        if (!$this->hasElement('missing_exchange_rates')) {
            return [];
        }

        return array_values(array_map(static fn ($item): string => trim($item->getText()), $this->getElement('missing_exchange_rates')->findAll('css', 'li')));
    }

    /** @return array<string, string> */
    protected function getDefinedElements(): array
    {
        return array_merge($this->getFormElements(), [
            'test_connection' => '[data-test-test-connection]',
            'missing_exchange_rates' => '[data-test-missing-exchange-rates]',
        ]);
    }
}
