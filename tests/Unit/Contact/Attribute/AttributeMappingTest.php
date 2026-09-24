<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Contact\Attribute;

use Odiseo\SyliusBrevoPlugin\Contact\Attribute\AttributeMapping;
use PHPUnit\Framework\TestCase;

final class AttributeMappingTest extends TestCase
{
    public function testItUsesBrevoNamesUppercaseAndOverrides(): void
    {
        $mapping = new AttributeMapping(['last_name' => 'apellido', 'birthday' => false]);

        self::assertSame('FIRSTNAME', $mapping->brevoName('first_name'));
        self::assertSame('SMS', $mapping->brevoName('phone'));
        self::assertSame('APELLIDO', $mapping->brevoName('last_name'));
        self::assertSame('TOTAL_SPENT', $mapping->brevoName('total_spent'));
        self::assertNull($mapping->brevoName('birthday'));
    }
}
