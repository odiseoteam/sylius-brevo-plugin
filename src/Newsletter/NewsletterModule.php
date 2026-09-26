<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Newsletter;

use Odiseo\SyliusBrevoPlugin\Contact\ContactsModule;
use Odiseo\SyliusBrevoPlugin\Module\DependentModuleInterface;

/** Works with a newsletter list chosen. */
final class NewsletterModule implements DependentModuleInterface
{
    public const CODE = 'newsletter';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getLabel(): string
    {
        return 'odiseo_brevo.module.newsletter';
    }

    public function getRequiredModules(): array
    {
        return [ContactsModule::CODE];
    }
}
