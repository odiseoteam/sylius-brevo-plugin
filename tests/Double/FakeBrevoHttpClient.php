<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Double;

use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\ErrorResponseMapper;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoHttpClientInterface;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;

/**
 * Records requests and replies with queued results. Unqueued calls get an empty 200 and queued
 * error responses throw like the real transport.
 *
 * With a storage path the state lives in a file, so Behat contexts and the kernel serving the
 * browser (two containers) see the same queue and requests.
 */
final class FakeBrevoHttpClient implements BrevoHttpClientInterface
{
    /** @var array<string, list<BrevoResponse|BrevoException>> */
    private array $queue = [];

    /** @var list<RecordedRequest> */
    private array $requests = [];

    public function __construct(
        private readonly ?string $storagePath = null,
    ) {
    }

    public function request(
        Credentials $credentials,
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
    ): BrevoResponse {
        $this->load();

        $this->requests[] = new RecordedRequest($credentials->apiKey, $method, self::normalize($path), $query, $json);
        $result = array_shift($this->queue[self::key($method, $path)]) ?? new BrevoResponse(200);

        $this->save();

        if ($result instanceof BrevoException) {
            throw $result;
        }

        if ($result->statusCode >= 400) {
            throw ErrorResponseMapper::map($result->statusCode, $result->data, $result->headers);
        }

        return $result;
    }

    public function queue(string $method, string $path, BrevoResponse|BrevoException $result): void
    {
        $this->load();

        if ($result instanceof BrevoException && null !== $this->storagePath) {
            // The trace may hold unserializable arguments.
            (new \ReflectionProperty(\Exception::class, 'trace'))->setValue($result, []);
        }

        $this->queue[self::key($method, $path)][] = $result;

        $this->save();
    }

    /** @return list<RecordedRequest> */
    public function requests(?string $method = null, ?string $path = null): array
    {
        $this->load();

        return array_values(array_filter(
            $this->requests,
            static fn (RecordedRequest $request): bool => (null === $method || $request->method === strtoupper($method)) &&
                (null === $path || $request->path === self::normalize($path)),
        ));
    }

    public function lastRequest(): ?RecordedRequest
    {
        $requests = $this->requests();

        return $requests[array_key_last($requests) ?? -1] ?? null;
    }

    public function reset(): void
    {
        $this->queue = [];
        $this->requests = [];

        if (null !== $this->storagePath && is_file($this->storagePath)) {
            unlink($this->storagePath);
        }
    }

    private function load(): void
    {
        if (null === $this->storagePath || !is_file($this->storagePath)) {
            return;
        }

        /** @var array{queue: array<string, list<BrevoResponse|BrevoException>>, requests: list<RecordedRequest>} $state */
        $state = unserialize((string) file_get_contents($this->storagePath));
        $this->queue = $state['queue'];
        $this->requests = $state['requests'];
    }

    private function save(): void
    {
        if (null === $this->storagePath) {
            return;
        }

        if (!is_dir(\dirname($this->storagePath))) {
            mkdir(\dirname($this->storagePath), 0777, true);
        }

        file_put_contents($this->storagePath, serialize(['queue' => $this->queue, 'requests' => $this->requests]));
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
