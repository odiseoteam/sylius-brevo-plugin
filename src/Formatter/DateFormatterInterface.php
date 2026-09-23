<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Formatter;

interface DateFormatterInterface
{
    /** ISO 8601 in UTC with milliseconds, e.g. 2026-09-23T10:00:00.000Z (orders, events). */
    public function formatDateTime(\DateTimeInterface $dateTime): string;

    /** Calendar date, e.g. 2026-09-23 (contact date attributes). */
    public function formatDate(\DateTimeInterface $date): string;
}
