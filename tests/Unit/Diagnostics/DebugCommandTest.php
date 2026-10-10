<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Diagnostics;

use Odiseo\SyliusBrevoPlugin\Client\Api\AccountApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\AuthenticationException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\Account;
use Odiseo\SyliusBrevoPlugin\Client\Model\Plan;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Diagnostics\Command\DebugCommand;
use Odiseo\SyliusBrevoPlugin\Diagnostics\DebugInfoProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Odiseo\SyliusBrevoPlugin\Module\ModuleRegistry;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Odiseo\SyliusBrevoPlugin\Double\DummyModule;

final class DebugCommandTest extends TestCase
{
    public function testItShowsTheConfigurationAndTheConnectionOfEachChannel(): void
    {
        $accountApi = $this->createStub(AccountApiInterface::class);
        $accountApi->method('getAccount')->willReturn(new Account('owner@example.com', plans: [new Plan('free')]));

        $tester = new CommandTester($this->command($accountApi));

        self::assertSame(Command::SUCCESS, $tester->execute([]));

        $display = $tester->getDisplay();
        self::assertStringContainsString('Channel WEB', $display);
        self::assertStringContainsString('enabled', $display);
        self::assertStringContainsString('…cret (own)', $display);
        self::assertStringNotContainsString('xkeysib-secret', $display);
        self::assertStringContainsString('dummy: on', $display);
        self::assertStringContainsString('#12', $display);
        self::assertStringContainsString('OK: owner@example.com (free)', $display);
        self::assertStringContainsString('sync (sent after each response', $display);
        self::assertStringContainsString('Extra row', $display);
        self::assertStringContainsString('Channel OTHER', $display);
        self::assertStringContainsString('not configured', $display);
    }

    public function testARejectedApiKeyFails(): void
    {
        $accountApi = $this->createStub(AccountApiInterface::class);
        $accountApi->method('getAccount')->willThrowException(new AuthenticationException('Key not found', 401));

        $tester = new CommandTester($this->command($accountApi));

        self::assertSame(Command::FAILURE, $tester->execute(['--channel' => 'WEB']));
        self::assertStringContainsString('FAILED: Brevo rejects the API key', $tester->getDisplay());
        self::assertStringNotContainsString('Channel OTHER', $tester->getDisplay());
    }

    public function testBrevoIsNotCalledWithoutConnection(): void
    {
        $accountApi = $this->createMock(AccountApiInterface::class);
        $accountApi->expects(self::never())->method('getAccount');

        $tester = new CommandTester($this->command($accountApi, 'doctrine://default?queue_name=odiseo_brevo'));

        self::assertSame(Command::SUCCESS, $tester->execute(['--no-connection' => true]));
        self::assertStringNotContainsString('Connection', $tester->getDisplay());
        self::assertStringContainsString('doctrine (needs "messenger:consume odiseo_brevo" running)', $tester->getDisplay());
    }

    public function testAnUnknownChannelIsInvalid(): void
    {
        self::assertSame(Command::INVALID, (new CommandTester($this->command($this->createStub(AccountApiInterface::class))))->execute(['--channel' => 'NOPE']));
    }

    private function command(AccountApiInterface $accountApi, string $transportDsn = 'sync://'): DebugCommand
    {
        $web = new Channel();
        $web->setCode('WEB');
        $other = new Channel();
        $other->setCode('OTHER');

        $channelRepository = $this->createStub(ChannelRepositoryInterface::class);
        $channelRepository->method('findAll')->willReturn([$web, $other]);

        $configuration = new ChannelConfiguration();
        $configuration->setChannel($web);
        $configuration->setEnabled(true);
        $configuration->setApiKey('xkeysib-secret');
        $configuration->setModules(['dummy']);

        $configurationRepository = $this->createStub(ChannelConfigurationRepositoryInterface::class);
        $configurationRepository->method('findOneByChannel')->willReturnCallback(static fn (ChannelInterface $channel): ?ChannelConfiguration => $channel === $web ? $configuration : null);

        $settings = new BrevoSettings('WEB', new Credentials('xkeysib-secret'), 'Shop', 'shop@example.com', ['dummy'], customersListId: 12);
        $configurationProvider = $this->createStub(ConfigurationProviderInterface::class);
        $configurationProvider->method('getSettings')->willReturnCallback(static fn (ChannelInterface $channel): ?BrevoSettings => $channel === $web ? $settings : null);
        $configurationProvider->method('getCredentials')->willReturn($settings->credentials);

        $infoProvider = new class() implements DebugInfoProviderInterface {
            public function provide(ChannelInterface $channel, ?BrevoSettings $settings): array
            {
                return null === $settings ? [] : ['Extra row' => 'from a provider'];
            }
        };

        return new DebugCommand($channelRepository, $configurationRepository, $configurationProvider, new ModuleRegistry([new DummyModule()]), $accountApi, [$infoProvider], $transportDsn);
    }
}
