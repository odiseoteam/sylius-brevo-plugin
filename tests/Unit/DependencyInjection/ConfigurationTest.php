<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\DependencyInjection;

use Odiseo\SyliusBrevoPlugin\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testItHasSensibleDefaults(): void
    {
        self::assertSame([
            'api' => ['key' => null, 'base_url' => 'https://api.brevo.com/v3', 'timeout' => 10.0, 'max_retries' => 2],
            'phone' => ['default_region' => null],
            'url' => ['image_filter' => 'sylius_shop_product_large_thumbnail'],
        ], $this->process([]));
    }

    public function testItAcceptsADefaultPhoneRegion(): void
    {
        self::assertSame(['default_region' => 'AR'], $this->process(['phone' => ['default_region' => 'AR']])['phone']);
    }

    public function testItRejectsAnInvalidPhoneRegion(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process(['phone' => ['default_region' => 'ARG']]);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}
