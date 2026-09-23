<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Client\Exception;

use Odiseo\SyliusBrevoPlugin\Client\Exception\AuthenticationException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\ErrorResponseMapper;
use Odiseo\SyliusBrevoPlugin\Client\Exception\NotFoundException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\RateLimitException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\ServerException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ErrorResponseMapperTest extends TestCase
{
    /** @param class-string $expectedClass */
    #[DataProvider('statusCodes')]
    public function testItMapsStatusCodes(int $statusCode, string $expectedClass, bool $retryable): void
    {
        $exception = ErrorResponseMapper::map($statusCode, ['code' => 'some_code', 'message' => 'Oops'], []);

        self::assertInstanceOf($expectedClass, $exception);
        self::assertSame($statusCode, $exception->statusCode);
        self::assertSame('some_code', $exception->errorCode);
        self::assertSame($retryable, $exception->isRetryable());
    }

    /** @return iterable<string, array{int, class-string, bool}> */
    public static function statusCodes(): iterable
    {
        yield 'bad request' => [400, ValidationException::class, false];
        yield 'unauthorized' => [401, AuthenticationException::class, false];
        yield 'forbidden' => [403, AuthenticationException::class, false];
        yield 'not found' => [404, NotFoundException::class, false];
        yield 'unprocessable' => [422, ValidationException::class, false];
        yield 'rate limited' => [429, RateLimitException::class, true];
        yield 'server error' => [500, ServerException::class, true];
        yield 'unavailable' => [503, ServerException::class, true];
    }

    public function testItReadsTheRateLimitReset(): void
    {
        $exception = ErrorResponseMapper::map(429, [], ['x-sib-ratelimit-reset' => ['12']]);

        self::assertInstanceOf(RateLimitException::class, $exception);
        self::assertSame(12, $exception->retryAfter);
    }

    public function testItPrefersRetryAfter(): void
    {
        $exception = ErrorResponseMapper::map(429, [], ['retry-after' => ['3'], 'x-sib-ratelimit-reset' => ['12']]);

        self::assertInstanceOf(RateLimitException::class, $exception);
        self::assertSame(3, $exception->retryAfter);
    }
}
