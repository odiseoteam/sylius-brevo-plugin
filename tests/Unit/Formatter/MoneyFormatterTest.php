<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Formatter;

use Odiseo\SyliusBrevoPlugin\Formatter\MoneyFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyFormatterTest extends TestCase
{
    #[DataProvider('amounts')]
    public function testItFormatsSyliusAmounts(int $amount, string $currencyCode, float $expected): void
    {
        self::assertSame($expected, (new MoneyFormatter())->format($amount, $currencyCode));
    }

    /** @return iterable<string, array{int, string, float}> */
    public static function amounts(): iterable
    {
        yield 'two fraction digits' => [1999, 'USD', 19.99];
        yield 'lowercase code' => [1999, 'eur', 19.99];
        yield 'negative' => [-550, 'USD', -5.5];
        yield 'zero' => [0, 'USD', 0.0];
        yield 'no fraction digits rounds' => [1999, 'JPY', 20.0];
        yield 'no fraction digits exact' => [150000, 'CLP', 1500.0];
        yield 'unknown currency uses default digits' => [1234, 'XYZ', 12.34];
    }
}
