<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\Account;

final class AccountApi implements AccountApiInterface
{
    public function __construct(private readonly BrevoHttpClientInterface $client)
    {
    }

    public function getAccount(Credentials $credentials): Account
    {
        return Account::fromArray($this->client->request($credentials, 'GET', '/account')->data);
    }
}
