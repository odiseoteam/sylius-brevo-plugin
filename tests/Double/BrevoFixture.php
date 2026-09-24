<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Double;

use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;

/** Recorded Brevo responses from tests/Fixtures/brevo. */
final class BrevoFixture
{
    public static function response(string $name, int $statusCode = 200): BrevoResponse
    {
        /** @var array<array-key, mixed> $data */
        $data = json_decode((string) file_get_contents(__DIR__ . '/../Fixtures/brevo/' . $name . '.json'), true, flags: \JSON_THROW_ON_ERROR);

        return new BrevoResponse($statusCode, $data);
    }
}
