<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Formatter;

use Odiseo\SyliusBrevoPlugin\Formatter\DateFormatter;
use PHPUnit\Framework\TestCase;

final class DateFormatterTest extends TestCase
{
    public function testItFormatsDateTimesInUtcWithMilliseconds(): void
    {
        $dateTime = new \DateTime('2026-09-23 21:30:15.123456', new \DateTimeZone('America/Argentina/Buenos_Aires'));

        self::assertSame('2026-09-24T00:30:15.123Z', (new DateFormatter())->formatDateTime($dateTime));
        self::assertSame('America/Argentina/Buenos_Aires', $dateTime->getTimezone()->getName());
    }

    public function testItFormatsDatesWithoutShiftingTheDay(): void
    {
        $date = new \DateTimeImmutable('1990-05-17 23:00', new \DateTimeZone('America/Argentina/Buenos_Aires'));

        self::assertSame('1990-05-17', (new DateFormatter())->formatDate($date));
    }
}
