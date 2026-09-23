<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Module;

use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Module\ModuleChecker;
use Odiseo\SyliusBrevoPlugin\Module\ModuleRegistry;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Channel;
use Tests\Odiseo\SyliusBrevoPlugin\Double\DummyModule;

final class ModuleCheckerTest extends TestCase
{
    public function testAModuleIsEnabledWhenRegisteredAndOnForTheChannel(): void
    {
        $checker = $this->checker(new BrevoSettings('WEB', new Credentials('key'), modules: ['dummy', 'unknown']));

        self::assertTrue($checker->isEnabled(new Channel(), 'dummy'));
        self::assertFalse($checker->isEnabled(new Channel(), 'unknown'));
        self::assertFalse($checker->isEnabled(new Channel(), 'sms'));
    }

    public function testNothingIsEnabledWithoutSettings(): void
    {
        self::assertFalse($this->checker(null)->isEnabled(new Channel(), 'dummy'));
    }

    public function testTheRegistryRejectsDuplicatedCodes(): void
    {
        $this->expectException(\LogicException::class);

        new ModuleRegistry([new DummyModule(), new DummyModule()]);
    }

    private function checker(?BrevoSettings $settings): ModuleChecker
    {
        $provider = $this->createStub(ConfigurationProviderInterface::class);
        $provider->method('getSettings')->willReturn($settings);

        return new ModuleChecker($provider, new ModuleRegistry([new DummyModule()]));
    }
}
