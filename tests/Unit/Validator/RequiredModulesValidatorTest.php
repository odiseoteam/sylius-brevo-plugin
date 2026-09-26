<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Validator;

use Odiseo\SyliusBrevoPlugin\Contact\ContactsModule;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Odiseo\SyliusBrevoPlugin\Module\ModuleRegistry;
use Odiseo\SyliusBrevoPlugin\Newsletter\NewsletterModule;
use Odiseo\SyliusBrevoPlugin\Validator\Constraints\RequiredModules;
use Odiseo\SyliusBrevoPlugin\Validator\Constraints\RequiredModulesValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @extends ConstraintValidatorTestCase<RequiredModulesValidator> */
final class RequiredModulesValidatorTest extends ConstraintValidatorTestCase
{
    public function testAModuleNeedsItsRequiredModules(): void
    {
        $this->validator->validate($this->configuration(['newsletter']), new RequiredModules());

        $this->buildViolation('odiseo_brevo.channel_configuration.modules.required')
            ->setParameter('%module%', 'Newsletter')
            ->setParameter('%required%', 'Contacts')
            ->atPath('property.path.modules')
            ->assertRaised()
        ;
    }

    public function testRequiredModulesEnabledAreFine(): void
    {
        $this->validator->validate($this->configuration(['contacts', 'newsletter']), new RequiredModules());
        $this->validator->validate($this->configuration(['contacts']), new RequiredModules());

        $this->assertNoViolation();
    }

    protected function createValidator(): RequiredModulesValidator
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnMap([
            ['odiseo_brevo.module.contacts', [], null, null, 'Contacts: customers as Brevo contacts'],
            ['odiseo_brevo.module.newsletter', [], null, null, 'Newsletter: Brevo list'],
        ]);

        return new RequiredModulesValidator(new ModuleRegistry([new ContactsModule(), new NewsletterModule()]), $translator);
    }

    /** @param list<string> $modules */
    private function configuration(array $modules): ChannelConfiguration
    {
        $configuration = new ChannelConfiguration();
        $configuration->setModules($modules);

        return $configuration;
    }
}
