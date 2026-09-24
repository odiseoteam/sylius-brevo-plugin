<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

/** Values of Brevo's `identifierType`. */
enum ContactIdentifierType: string
{
    case Email = 'email_id';
    case ExtId = 'ext_id';
    case ContactId = 'contact_id';
    case Phone = 'phone_id';
}
