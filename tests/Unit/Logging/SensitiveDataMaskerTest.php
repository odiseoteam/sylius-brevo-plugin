<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Logging;

use Odiseo\SyliusBrevoPlugin\Logging\SensitiveDataMasker;
use PHPUnit\Framework\TestCase;

final class SensitiveDataMaskerTest extends TestCase
{
    public function testItMasksEmails(): void
    {
        $masker = new SensitiveDataMasker();

        self::assertSame('/contacts/d***@odiseo.com.ar', $masker->mask('/contacts/diego@odiseo.com.ar'));
        self::assertSame('/contacts/d***%40odiseo.com.ar', $masker->mask('/contacts/diego%40odiseo.com.ar'));
        self::assertSame('a***@b.io and j***@example.com', $masker->mask('a@b.io and john.doe+x@example.com'));
    }

    public function testItLeavesOtherTextUntouched(): void
    {
        self::assertSame('/contacts/42', (new SensitiveDataMasker())->mask('/contacts/42'));
    }
}
