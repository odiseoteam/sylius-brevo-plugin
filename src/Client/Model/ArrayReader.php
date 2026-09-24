<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Model;

/**
 * Tolerant reads from decoded Brevo payloads: missing or mistyped fields become defaults.
 *
 * @internal
 */
final class ArrayReader
{
    /** @param array<array-key, mixed> $data */
    public static function string(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && '' !== $value ? $value : null;
    }

    /** @param array<array-key, mixed> $data */
    public static function int(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : null);
    }

    /** @param array<array-key, mixed> $data */
    public static function bool(array $data, string $key): bool
    {
        return true === ($data[$key] ?? null);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public static function array(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<array<array-key, mixed>>
     */
    public static function arrays(array $data, string $key): array
    {
        return array_values(array_filter(self::array($data, $key), 'is_array'));
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<int>
     */
    public static function ints(array $data, string $key): array
    {
        return array_values(array_map('intval', array_filter(self::array($data, $key), 'is_numeric')));
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<string>
     */
    public static function strings(array $data, string $key): array
    {
        return array_values(array_filter(self::array($data, $key), 'is_string'));
    }

    /** @param array<array-key, mixed> $data */
    public static function dateTime(array $data, string $key): ?\DateTimeImmutable
    {
        $value = self::string($data, $key);
        if (null === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
