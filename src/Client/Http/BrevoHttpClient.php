<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Http;

use Odiseo\SyliusBrevoPlugin\Client\Exception\ErrorResponseMapper;
use Odiseo\SyliusBrevoPlugin\Client\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BrevoHttpClient implements BrevoHttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly float $timeout,
    ) {
    }

    public function request(
        Credentials $credentials,
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
    ): BrevoResponse {
        $options = [
            'headers' => [
                'accept' => 'application/json',
                'api-key' => $credentials->apiKey,
            ],
            'query' => $query,
            'timeout' => $this->timeout,
        ];

        if (null !== $json) {
            $options['json'] = $json;
        }

        try {
            $response = $this->httpClient->request($method, rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/'), $options);

            $statusCode = $response->getStatusCode();
            $headers = $response->getHeaders(false);
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        $data = $this->decode($content);

        if ($statusCode >= 400) {
            throw ErrorResponseMapper::map($statusCode, $data, $headers);
        }

        return new BrevoResponse($statusCode, $data, $headers);
    }

    /** @return array<array-key, mixed> */
    private function decode(string $content): array
    {
        if ('' === trim($content)) {
            return [];
        }

        try {
            $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Non-JSON bodies (e.g. gateway HTML errors) are kept for the error message.
            return ['message' => mb_substr($content, 0, 500)];
        }

        return is_array($data) ? $data : [];
    }
}
