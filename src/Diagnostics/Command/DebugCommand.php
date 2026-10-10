<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Diagnostics\Command;

use Odiseo\SyliusBrevoPlugin\Client\Api\AccountApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\AuthenticationException;
use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Client\Model\Plan;
use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Diagnostics\DebugInfoProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Module\ModuleRegistryInterface;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'odiseo:brevo:debug',
    description: 'Shows the Brevo configuration each channel ends up with and checks its connection.',
)]
final class DebugCommand extends Command
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     * @param iterable<DebugInfoProviderInterface> $infoProviders
     */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ChannelConfigurationRepositoryInterface $configurationRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ModuleRegistryInterface $moduleRegistry,
        private readonly AccountApiInterface $accountApi,
        private readonly iterable $infoProviders,
        private readonly string $transportDsn,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Only this channel code')
            ->addOption('no-connection', null, InputOption::VALUE_NONE, 'Do not call Brevo')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $channelCode = $input->getOption('channel');
        $online = !(bool) $input->getOption('no-connection');

        $channels = array_filter(
            $this->channelRepository->findAll(),
            static fn (ChannelInterface $channel): bool => null === $channelCode || $channel->getCode() === $channelCode,
        );
        if ([] === $channels) {
            $io->error('No such channel.');

            return Command::INVALID;
        }

        // The scheme only: the DSN may carry credentials.
        $scheme = strstr($this->transportDsn, ':', true) ?: $this->transportDsn;
        $io->definitionList(['Queue transport' => 'sync' === $scheme ? 'sync (sent after each response, no worker needed)' : sprintf('%s (needs "messenger:consume odiseo_brevo" running)', $scheme)]);

        $failed = false;
        foreach ($channels as $channel) {
            $io->section(sprintf('Channel %s', (string) $channel->getCode()));

            $configuration = $this->configurationRepository->findOneByChannel($channel);
            $settings = $this->configurationProvider->getSettings($channel);
            $credentials = null === $configuration ? null : $this->configurationProvider->getCredentials($configuration);

            $rows = $this->configurationRows($configuration, $settings, $credentials);
            if ($online && null !== $settings) {
                [$connected, $rows['Connection']] = $this->connection($settings->credentials);
                $failed = $failed || !$connected;
            }
            foreach ($this->infoProviders as $provider) {
                $rows = [...$rows, ...$provider->provide($channel, $settings)];
            }

            $io->definitionList(...array_map(static fn (string $label, string $value): array => [$label => $value], array_keys($rows), $rows));
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    /** @return array<string, string> */
    private function configurationRows(?ChannelConfigurationInterface $configuration, ?BrevoSettings $settings, ?Credentials $credentials): array
    {
        if (null === $configuration) {
            return ['Brevo' => 'not configured'];
        }

        $rows = [
            'Brevo' => match (true) {
                !$configuration->isEnabled() => 'disabled',
                null === $credentials => 'off: no API key',
                default => 'enabled',
            },
            'API key' => match (true) {
                null === $credentials => 'none',
                $configuration->hasApiKey() => sprintf('%s (own)', self::mask($credentials->apiKey)),
                default => sprintf('%s (default)', self::mask($credentials->apiKey)),
            },
        ];

        $modules = [];
        foreach (array_keys($this->moduleRegistry->all()) as $code) {
            $modules[] = sprintf('%s: %s', $code, $configuration->hasModule($code) ? 'on' : 'off');
        }
        $rows['Modules'] = implode(', ', $modules);

        if (null === $settings) {
            return $rows;
        }

        return [...$rows, ...[
            'Sender' => null === $settings->senderEmail ? '-' : trim(sprintf('%s <%s>', (string) $settings->senderName, $settings->senderEmail)),
            'Customers list' => self::id($settings->customersListId),
            'Newsletter list' => self::id($settings->newsletterListId) . (null === $settings->newsletterListId || $settings->hasNewsletter() ? '' : ' (newsletter off)'),
            'Double opt-in template' => self::id($settings->doubleOptInTemplateId),
            'Guest contacts' => $settings->syncingGuestContacts ? 'synced' : 'not synced',
            'Removed customers' => $settings->deletingContactsOfRemovedCustomers ? 'contact deleted' : 'contact kept',
            'Tracker client key' => $settings->trackerClientKey ?? '-',
        ]];
    }

    /** @return array{bool, string} */
    private function connection(Credentials $credentials): array
    {
        try {
            $account = $this->accountApi->getAccount($credentials);
        } catch (AuthenticationException) {
            return [false, 'FAILED: Brevo rejects the API key'];
        } catch (BrevoException $exception) {
            return [false, sprintf('FAILED: %s', $exception->getMessage())];
        }

        $plans = implode(', ', array_map(static fn (Plan $plan): string => $plan->type, $account->plans));

        return [true, sprintf('OK: %s (%s)', $account->email, '' === $plans ? '-' : $plans)];
    }

    private static function mask(#[\SensitiveParameter] string $apiKey): string
    {
        return '…' . substr($apiKey, -4);
    }

    private static function id(?int $id): string
    {
        return null === $id ? '-' : sprintf('#%d', $id);
    }
}
