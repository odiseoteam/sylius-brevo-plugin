<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Http;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Logging\SensitiveDataMasker;
use Psr\Log\LoggerInterface;

/** Logs every call without credentials or request bodies. */
final class LoggingBrevoHttpClient implements BrevoHttpClientInterface
{
    public function __construct(
        private readonly BrevoHttpClientInterface $decorated,
        private readonly LoggerInterface $logger,
        private readonly SensitiveDataMasker $masker,
    ) {
    }

    public function request(
        Credentials $credentials,
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
    ): BrevoResponse {
        $startedAt = microtime(true);
        $context = ['method' => $method, 'path' => $this->masker->mask($path)];

        try {
            $response = $this->decorated->request($credentials, $method, $path, $query, $json);
        } catch (BrevoException $exception) {
            $this->logger->error('Brevo request failed', $context + [
                'status' => $exception->statusCode,
                'error_code' => $exception->errorCode,
                'error' => $this->masker->mask($exception->getMessage()),
                'duration_ms' => $this->elapsedMs($startedAt),
            ]);

            throw $exception;
        }

        $this->logger->info('Brevo request', $context + [
            'status' => $response->statusCode,
            'duration_ms' => $this->elapsedMs($startedAt),
        ]);

        return $response;
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
