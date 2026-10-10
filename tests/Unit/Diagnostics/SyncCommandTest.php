<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Diagnostics;

use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Diagnostics\Command\SyncCommand;
use Odiseo\SyliusBrevoPlugin\Diagnostics\SyncStep;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\Channel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class SyncCommandTest extends TestCase
{
    /** @var list<array{string, mixed, mixed}> command, --channel and --dry-run of each run */
    private array $runs = [];

    public function testItRunsTheStepsOfTheModulesThatAreOnInOrder(): void
    {
        $tester = $this->tester();

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame([['test:categories', null, false], ['test:contacts', null, null]], $this->runs);
        self::assertStringContainsString('skipped: orders module off', $tester->getDisplay());
    }

    public function testTheChannelAndTheDryRunReachTheStepsThatTakeThem(): void
    {
        self::assertSame(Command::SUCCESS, $this->tester()->execute(['--channel' => 'WEB', '--dry-run' => true]));
        self::assertSame([['test:categories', 'WEB', true], ['test:contacts', 'WEB', null]], $this->runs);
    }

    public function testOnlyTheGivenStepsRun(): void
    {
        self::assertSame(Command::SUCCESS, $this->tester()->execute(['--step' => ['contacts']]));
        self::assertSame([['test:contacts', null, null]], $this->runs);

        self::assertSame(Command::INVALID, $this->tester()->execute(['--step' => ['nope']]));
    }

    public function testAFailedStepFailsTheSyncWithoutStoppingIt(): void
    {
        $tester = $this->tester(categoriesStatus: Command::FAILURE);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertCount(2, $this->runs);
        self::assertStringContainsString('FAILED', $tester->getDisplay());
    }

    public function testNoStepRunsForAChannelWithoutBrevo(): void
    {
        self::assertSame(Command::SUCCESS, $this->tester()->execute(['--channel' => 'OTHER']));
        self::assertSame([], $this->runs);
    }

    private function tester(int $categoriesStatus = Command::SUCCESS): CommandTester
    {
        $this->runs = [];

        $web = new Channel();
        $web->setCode('WEB');
        $other = new Channel();
        $other->setCode('OTHER');

        $channelRepository = $this->createStub(ChannelRepositoryInterface::class);
        $channelRepository->method('findAll')->willReturn([$web, $other]);

        $configurationProvider = $this->createStub(ConfigurationProviderInterface::class);
        $configurationProvider->method('getSettings')->willReturnCallback(
            static fn (Channel $channel): ?BrevoSettings => $channel === $web ? new BrevoSettings('WEB', new Credentials('key'), modules: ['catalog', 'contacts']) : null,
        );

        $application = new Application();
        $application->add($this->step('test:categories', $categoriesStatus, dryRun: true));
        $application->add($this->step('test:contacts', Command::SUCCESS, dryRun: false));
        $application->add($this->step('test:orders', Command::SUCCESS, dryRun: true));
        $application->add(new SyncCommand([
            new SyncStep('categories', 'catalog', 'test:categories'),
            new SyncStep('contacts', 'contacts', 'test:contacts'),
            new SyncStep('orders', 'orders', 'test:orders'),
        ], $channelRepository, $configurationProvider));

        return new CommandTester($application->find('odiseo:brevo:sync'));
    }

    private function step(string $name, int $status, bool $dryRun): Command
    {
        $record = function (string $name, mixed $channel, mixed $dryRun): void {
            $this->runs[] = [$name, $channel, $dryRun];
        };

        return new class($name, $status, $dryRun, $record) extends Command {
            public function __construct(
                string $name,
                private readonly int $status,
                private readonly bool $dryRun,
                private readonly \Closure $record,
            ) {
                parent::__construct($name);
            }

            protected function configure(): void
            {
                $this->addOption('channel', null, InputOption::VALUE_REQUIRED);
                if ($this->dryRun) {
                    $this->addOption('dry-run', null, InputOption::VALUE_NONE);
                }
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                ($this->record)((string) $this->getName(), $input->getOption('channel'), $this->dryRun ? $input->getOption('dry-run') : null);

                return $this->status;
            }
        };
    }
}
