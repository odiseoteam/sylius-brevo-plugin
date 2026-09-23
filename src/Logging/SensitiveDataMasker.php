<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Logging;

/** Masks personal data before it reaches the logs. */
final class SensitiveDataMasker
{
    private const EMAIL_PATTERN = '/([A-Za-z0-9._%+\-])[A-Za-z0-9._%+\-]*(@|%40)([A-Za-z0-9.\-]+\.[A-Za-z]{2,})/';

    /** "diego@odiseo.com.ar" becomes "d***@odiseo.com.ar". */
    public function mask(string $value): string
    {
        return preg_replace(self::EMAIL_PATTERN, '$1***$2$3', $value) ?? $value;
    }
}
