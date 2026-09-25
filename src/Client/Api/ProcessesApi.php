<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\Process;

final class ProcessesApi implements ProcessesApiInterface
{
    public function __construct(private readonly BrevoHttpClientInterface $client)
    {
    }

    public function get(Credentials $credentials, int $processId): Process
    {
        return Process::fromArray($this->client->request($credentials, 'GET', sprintf('/processes/%d', $processId))->data);
    }
}
