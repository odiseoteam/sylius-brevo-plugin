<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Http;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;

/** Single outbound point to the Brevo REST API. */
interface BrevoHttpClientInterface
{
    /**
     * @param string $path Relative to the API base URL, e.g. "/contacts"
     * @param array<string, mixed> $query
     * @param array<array-key, mixed>|null $json Request body, sent as JSON
     *
     * @throws BrevoException
     */
    public function request(
        Credentials $credentials,
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
    ): BrevoResponse;
}
