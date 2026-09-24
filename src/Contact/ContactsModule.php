<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Odiseo\SyliusBrevoPlugin\Module\ModuleInterface;

final class ContactsModule implements ModuleInterface
{
    public const CODE = 'contacts';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getLabel(): string
    {
        return 'odiseo_brevo.module.contacts';
    }
}
