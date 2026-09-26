<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Command;

use Odiseo\SyliusBrevoPlugin\Client\Api\ContactsApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Api\ProcessesApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\Process;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\AccountAttributesInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactLists;
use Odiseo\SyliusBrevoPlugin\Contact\ContactPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactTargetResolverInterface;
use Odiseo\SyliusBrevoPlugin\Contact\CustomerBatchesInterface;
use Odiseo\SyliusBrevoPlugin\Contact\CustomerFilter;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'odiseo:brevo:contacts:sync',
    description: 'Imports existing customers into Brevo in bulk (initial load or catch-up).',
)]
final class SyncContactsCommand extends Command
{
    private const POLL_SECONDS = 5;

    /** @var \Closure(int): void */
    private readonly \Closure $sleep;

    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     * @param (\Closure(int): void)|null $sleep
     */
    public function __construct(
        private readonly ContactTargetResolverInterface $targetResolver,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly CustomerBatchesInterface $customerBatches,
        private readonly ContactPayloadBuilderInterface $payloadBuilder,
        private readonly AccountAttributesInterface $accountAttributes,
        private readonly ContactsApiInterface $contactsApi,
        private readonly ProcessesApiInterface $processesApi,
        ?\Closure $sleep = null,
    ) {
        parent::__construct();

        $this->sleep = $sleep ?? static function (int $seconds): void {
            sleep($seconds);
        };
    }

    protected function configure(): void
    {
        $this
            ->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Only the Brevo account of this channel code')
            ->addOption('list', null, InputOption::VALUE_REQUIRED, 'Brevo list id for everyone (defaults to the channel customers and newsletter lists)')
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Only customers created or updated since this date, e.g. 2026-01-01')
            ->addOption('only-subscribed', null, InputOption::VALUE_NONE, 'Only newsletter subscribers')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Contacts per import', '1000')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Count and preview, without sending')
            ->addOption('no-wait', null, InputOption::VALUE_NONE, 'Do not wait for Brevo to process the imports')
            ->addOption('wait-timeout', null, InputOption::VALUE_REQUIRED, 'Seconds to wait for the imports', '600')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $since = self::option($input, 'since');
            $since = null === $since ? null : new \DateTimeImmutable($since);
        } catch (\Exception) {
            $io->error('Invalid --since date.');

            return Command::INVALID;
        }

        $batchSize = max(1, (int) self::option($input, 'batch-size'));
        $list = self::option($input, 'list');
        $listOption = null === $list ? null : (int) $list;
        $channelCode = self::option($input, 'channel');

        $channels = array_values(array_filter(
            $this->targetResolver->accounts(),
            static fn (ChannelInterface $channel): bool => null === $channelCode || $channel->getCode() === $channelCode,
        ));
        if ([] === $channels) {
            $io->warning('No channel with an enabled Brevo configuration and the contacts module on.');

            return Command::SUCCESS;
        }

        $failed = false;
        foreach ($channels as $channel) {
            $failed = !$this->syncAccount($io, $channel, $listOption, $since, (bool) $input->getOption('only-subscribed'), $batchSize, $input) || $failed;
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    private function syncAccount(
        SymfonyStyle $io,
        ChannelInterface $channel,
        ?int $listOption,
        ?\DateTimeInterface $since,
        bool $onlySubscribed,
        int $batchSize,
        InputInterface $input,
    ): bool {
        $channelId = $channel->getId();
        $channelCode = (string) $channel->getCode();
        $settings = $this->configurationProvider->getSettings($channel);
        if (null === $settings) {
            return true;
        }

        $io->section(sprintf('Brevo account of channel %s', $channelCode));

        if (null === $listOption && null === $settings->customersListId && !$settings->hasNewsletter()) {
            $io->error('No list to import into: choose the customers or newsletter list in Brevo > Configuration, or pass --list.');

            return false;
        }

        $filter = new CustomerFilter($settings->syncingGuestContacts, $since, $onlySubscribed, $settings->hasNewsletter());
        $total = $this->customerBatches->count($filter);
        $io->text(null === $listOption
            ? sprintf('%d customers into the customers list (%s) and, if subscribed, the newsletter list (%s).', $total, self::listLabel($settings->customersListId), self::listLabel($settings->hasNewsletter() ? $settings->newsletterListId : null))
            : sprintf('%d customers into list #%d.', $total, $listOption));

        if ((bool) $input->getOption('dry-run')) {
            $this->preview($io, $filter, $channel, $settings, $listOption);

            return true;
        }

        $attributes = $this->accountAttributes->names($settings->credentials);
        $processIds = [];
        $skipped = 0;

        $io->progressStart($total);
        foreach ($this->customerBatches->batches($filter, $batchSize) as $customers) {
            // The previous batch cleared the entity manager.
            $channel = $this->channelRepository->find($channelId) ?? $channel;

            // One import per set of lists: Brevo applies the lists to the whole import.
            $imports = [];
            foreach ($customers as $customer) {
                $listIds = null === $listOption ? ContactLists::of($customer, $settings) : [$listOption];
                if ([] === $listIds) {
                    ++$skipped;

                    continue;
                }

                $imports[implode(',', $listIds)]['lists'] = $listIds;
                $imports[implode(',', $listIds)]['contacts'][] = $this->payloadBuilder->build($customer, $channel)->withAttributesIn($attributes);
            }

            try {
                foreach ($imports as $import) {
                    $processIds[] = $this->contactsApi->import($settings->credentials, $import['contacts'], $import['lists']);
                }
            } catch (BrevoException $exception) {
                $io->progressFinish();
                $io->error(sprintf('Import failed after %d processes: %s', count($processIds), $exception->getMessage()));

                return false;
            }

            $io->progressAdvance(count($customers));
        }
        $io->progressFinish();

        if ($skipped > 0) {
            $io->note(sprintf('%d customers skipped: not subscribed and no customers list.', $skipped));
        }

        if ([] === $processIds || (bool) $input->getOption('no-wait')) {
            $io->success(sprintf('%d imports sent: %s.', count($processIds), implode(', ', $processIds) ?: '-'));

            return true;
        }

        return $this->waitFor($io, $settings->credentials, $processIds, (int) self::option($input, 'wait-timeout'));
    }

    private static function listLabel(?int $listId): string
    {
        return null === $listId ? 'none' : sprintf('#%d', $listId);
    }

    private static function option(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);

        return is_scalar($value) ? (string) $value : null;
    }

    private function preview(SymfonyStyle $io, CustomerFilter $filter, ChannelInterface $channel, BrevoSettings $settings, ?int $listOption): void
    {
        $rows = [];
        foreach ($this->customerBatches->batches($filter, 3) as $customers) {
            foreach ($customers as $customer) {
                $data = $this->payloadBuilder->build($customer, $channel);
                $listIds = null === $listOption ? ContactLists::of($customer, $settings) : [$listOption];
                $rows[] = [$data->email, $data->extId, implode(', ', $listIds) ?: '-', implode(', ', array_keys($data->attributes))];
            }

            break;
        }

        $io->table(['Email', 'ext_id', 'Lists', 'Attributes'], $rows);
        $io->note('Dry run: nothing was sent.');
    }

    /** @param non-empty-list<int> $processIds */
    private function waitFor(SymfonyStyle $io, Credentials $credentials, array $processIds, int $timeout): bool
    {
        $io->text('Waiting for Brevo to process the imports...');

        $processes = [];
        $deadline = time() + max(0, $timeout);
        do {
            foreach ($processIds as $processId) {
                if (!($processes[$processId] ?? null)?->isFinished()) {
                    $processes[$processId] = $this->processesApi->get($credentials, $processId);
                }
            }

            $pending = array_filter($processes, static fn (Process $process): bool => !$process->isFinished());
            if ([] !== $pending && time() < $deadline) {
                ($this->sleep)(self::POLL_SECONDS);
            }
        } while ([] !== $pending && time() < $deadline);

        $io->table(['Process', 'Status', 'Details'], array_map(
            static fn (Process $process): array => [$process->id, $process->status, (string) json_encode($process->importInfo)],
            array_values($processes),
        ));

        $failed = array_filter($processes, static fn (Process $process): bool => $process->isFinished() && Process::STATUS_COMPLETED !== $process->status);
        if ([] !== $failed) {
            $io->error('Some imports did not complete.');

            return false;
        }

        if ([] !== $pending) {
            $io->warning('Brevo is still processing; check the imports later in Brevo (Contacts > Import).');

            return true;
        }

        $io->success('Every import completed.');

        return true;
    }
}
