<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Http;

/** Reads how long Brevo asks to wait before the next call. */
final class RateLimitHeaders
{
    /** @param array<array-key, mixed> $headers Lower-cased names, as Symfony returns them */
    public static function secondsUntilReset(array $headers): ?int
    {
        foreach (['retry-after', 'x-sib-ratelimit-reset'] as $name) {
            $values = $headers[$name] ?? null;
            $value = is_array($values) ? ($values[0] ?? null) : null;

            if (is_numeric($value)) {
                return max(0, (int) ceil((float) $value));
            }
        }

        return null;
    }
}
