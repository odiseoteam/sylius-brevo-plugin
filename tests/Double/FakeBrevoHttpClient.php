<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Double;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Records requests and replies with queued results. Unqueued calls get an empty 200.
 */
final class FakeBrevoHttpClient implements BrevoHttpClientInterface, ResetInterface
{
    /** @var array<string, list<BrevoResponse|BrevoException>> */
    private array $queue = [];

    /** @var list<RecordedRequest> */
    private array $requests = [];

    public function request(
        Credentials $credentials,
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
    ): BrevoResponse {
        $this->requests[] = new RecordedRequest($credentials->apiKey, $method, self::normalize($path), $query, $json);

        $result = array_shift($this->queue[self::key($method, $path)]) ?? new BrevoResponse(200);

        if ($result instanceof BrevoException) {
            throw $result;
        }

        return $result;
    }

    public function queue(string $method, string $path, BrevoResponse|BrevoException $result): void
    {
        $this->queue[self::key($method, $path)][] = $result;
    }

    /** @return list<RecordedRequest> */
    public function requests(?string $method = null, ?string $path = null): array
    {
        return array_values(array_filter(
            $this->requests,
            static fn (RecordedRequest $request): bool => (null === $method || $request->method === strtoupper($method)) &&
                (null === $path || $request->path === self::normalize($path)),
        ));
    }

    public function lastRequest(): ?RecordedRequest
    {
        return $this->requests[array_key_last($this->requests) ?? -1] ?? null;
    }

    public function reset(): void
    {
        $this->queue = [];
        $this->requests = [];
    }

    private static function key(string $method, string $path): string
    {
        return strtoupper($method) . ' ' . self::normalize($path);
    }

    private static function normalize(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}
