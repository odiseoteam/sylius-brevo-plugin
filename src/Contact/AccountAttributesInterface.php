<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact;

use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;

interface AccountAttributesInterface
{
    /**
     * Contact attributes that exist in the account, cached for a while.
     *
     * @return list<string>
     */
    public function names(Credentials $credentials): array;

    public function forget(Credentials $credentials): void;
}
