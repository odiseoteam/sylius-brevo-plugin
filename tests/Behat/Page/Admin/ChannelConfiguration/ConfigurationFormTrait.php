<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Page\Admin\ChannelConfiguration;

use Webmozart\Assert\Assert;

trait ConfigurationFormTrait
{
    public function fillApiKey(string $apiKey): void
    {
        $this->getElement('api_key')->setValue($apiKey);
    }

    public function getApiKey(): string
    {
        $value = $this->getElement('api_key')->getValue();
        Assert::string($value);

        return $value;
    }

    public function fillSender(string $name, string $email): void
    {
        $this->getElement('sender_name')->setValue($name);
        $this->getElement('sender_email')->setValue($email);
    }

    public function chooseCustomersList(string $name): void
    {
        $this->chooseList('customers_list', $name);
    }

    public function chooseNewsletterList(string $name): void
    {
        $this->chooseList('newsletter_list', $name);
    }

    public function fillDoubleOptInTemplate(int $templateId): void
    {
        $this->getElement('double_opt_in_template')->setValue((string) $templateId);
    }

    private function chooseList(string $element, string $name): void
    {
        $select = $this->getElement($element);
        foreach ($select->findAll('css', 'option') as $option) {
            $value = $option->getValue();
            if (is_string($value) && str_starts_with($option->getText(), $name . ' (#')) {
                $select->selectOption($value);

                return;
            }
        }

        throw new \InvalidArgumentException(sprintf('No Brevo list "%s" to choose.', $name));
    }

    public function getModulesValidationMessage(): string
    {
        return (string) $this->getElement('modules')->find('css', '.invalid-feedback')?->getText();
    }

    public function enableModule(string $label): void
    {
        $this->getElement('modules')->checkField($label);
    }

    /** @return array<string, string> */
    protected function getDefinedElements(): array
    {
        /** @var array<string, string> $elements */
        $elements = array_merge(parent::getDefinedElements(), [
            'api_key' => '#odiseo_brevo_channel_configuration_apiKey',
            'channel' => '#odiseo_brevo_channel_configuration_channel',
            'customers_list' => '#odiseo_brevo_channel_configuration_customersListId',
            'double_opt_in_template' => '#odiseo_brevo_channel_configuration_doubleOptInTemplateId',
            'newsletter_list' => '#odiseo_brevo_channel_configuration_newsletterListId',
            'modules' => '[data-test-modules]',
            'sender_email' => '#odiseo_brevo_channel_configuration_senderEmail',
            'sender_name' => '#odiseo_brevo_channel_configuration_senderName',
        ]);

        return $elements;
    }
}
