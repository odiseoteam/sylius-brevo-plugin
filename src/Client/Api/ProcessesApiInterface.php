<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Api;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\Process;

interface ProcessesApiInterface
{
    /** @throws BrevoException */
    public function get(Credentials $credentials, int $processId): Process;
}
