<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Diagnostics\Command;

use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Diagnostics\SyncStep;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'odiseo:brevo:sync',
    description: 'Runs every Brevo sync of the modules that are on, in order (initial load or catch-up).',
)]
final class SyncCommand extends Command
{
    /**
     * @param iterable<SyncStep> $steps
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     */
    public function __construct(
        private readonly iterable $steps,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Only the Brevo account of this channel code')
            ->addOption('step', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only these steps')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be sent')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $channelCode = $input->getOption('channel');
        $channelCode = is_string($channelCode) ? $channelCode : null;
        /** @var list<string> $only */
        $only = $input->getOption('step');
        $dryRun = (bool) $input->getOption('dry-run');

        $steps = [];
        foreach ($this->steps as $step) {
            $steps[$step->name] = $step;
        }

        $unknown = array_diff($only, array_keys($steps));
        if ([] !== $unknown) {
            $io->error(sprintf('Unknown steps: %s. Available: %s.', implode(', ', $unknown), implode(', ', array_keys($steps))));

            return Command::INVALID;
        }

        $modules = $this->modules($channelCode);
        $summary = [];
        $failed = false;
        foreach ($steps as $step) {
            if ([] !== $only && !in_array($step->name, $only, true)) {
                continue;
            }

            if (!in_array($step->module, $modules, true)) {
                $summary[] = [$step->name, $step->command, sprintf('skipped: %s module off', $step->module)];

                continue;
            }

            $io->title(sprintf('%s (%s)', $step->name, $step->command));
            $status = $this->runStep($step, $channelCode, $dryRun, $output);
            $failed = $failed || Command::SUCCESS !== $status;
            $summary[] = [$step->name, $step->command, Command::SUCCESS === $status ? 'done' : 'FAILED'];
        }

        $io->title('Summary');
        $io->table(['Step', 'Command', 'Result'], $summary);

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    private function runStep(SyncStep $step, ?string $channelCode, bool $dryRun, OutputInterface $output): int
    {
        $command = $this->getApplication()?->find($step->command);
        if (null === $command) {
            return Command::FAILURE;
        }

        $arguments = [];
        if (null !== $channelCode && $command->getDefinition()->hasOption('channel')) {
            $arguments['--channel'] = $channelCode;
        }
        if ($dryRun && $command->getDefinition()->hasOption('dry-run')) {
            $arguments['--dry-run'] = true;
        }

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $command->run($input, $output);
    }

    /** @return list<string> modules on in the channels to sync */
    private function modules(?string $channelCode): array
    {
        $modules = [];
        foreach ($this->channelRepository->findAll() as $channel) {
            $settings = null === $channelCode || $channel->getCode() === $channelCode ? $this->configurationProvider->getSettings($channel) : null;
            if (null !== $settings) {
                $modules = [...$modules, ...$settings->modules];
            }
        }

        return array_values(array_unique($modules));
    }
}
