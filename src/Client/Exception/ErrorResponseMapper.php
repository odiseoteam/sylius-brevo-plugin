<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Exception;

use Odiseo\SyliusBrevoPlugin\Client\Http\RateLimitHeaders;

/** Turns a Brevo error response into the matching exception. */
final class ErrorResponseMapper
{
    /**
     * @param array<array-key, mixed> $data
     * @param array<string, list<string>> $headers
     */
    public static function map(int $statusCode, array $data, array $headers): BrevoException
    {
        $errorCode = is_string($data['code'] ?? null) ? $data['code'] : null;
        $message = sprintf(
            'Brevo API error %d%s: %s',
            $statusCode,
            null !== $errorCode ? sprintf(' (%s)', $errorCode) : '',
            is_string($data['message'] ?? null) ? $data['message'] : 'no message',
        );

        return match (true) {
            401 === $statusCode, 403 === $statusCode => new AuthenticationException($message, $statusCode, $errorCode),
            404 === $statusCode => new NotFoundException($message, $statusCode, $errorCode),
            429 === $statusCode => new RateLimitException($message, $errorCode, RateLimitHeaders::secondsUntilReset($headers)),
            $statusCode >= 500 => new ServerException($message, $statusCode, $errorCode),
            default => new ValidationException($message, $statusCode, $errorCode),
        };
    }
}
