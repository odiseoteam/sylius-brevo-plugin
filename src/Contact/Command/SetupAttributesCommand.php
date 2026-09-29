<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\Command;

use Odiseo\SyliusBrevoPlugin\Client\Api\AttributesApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Model\Attribute;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\AccountAttributesInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactTargetResolverInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'odiseo:brevo:attributes:setup',
    description: 'Creates in Brevo the contact attributes the plugin fills and the account lacks.',
)]
final class SetupAttributesCommand extends Command
{
    public function __construct(
        private readonly ContactTargetResolverInterface $targetResolver,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ContactPayloadBuilderInterface $payloadBuilder,
        private readonly AttributesApiInterface $attributesApi,
        private readonly AccountAttributesInterface $accountAttributes,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Only the Brevo account of this channel code')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be created')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $channelCode = $input->getOption('channel');
        $dryRun = (bool) $input->getOption('dry-run');

        $channels = array_filter(
            $this->targetResolver->accounts(),
            static fn ($channel): bool => null === $channelCode || $channel->getCode() === $channelCode,
        );
        if ([] === $channels) {
            $io->warning('No channel with an enabled Brevo configuration and the contacts module on.');

            return Command::SUCCESS;
        }

        $failed = false;
        foreach ($channels as $channel) {
            $settings = $this->configurationProvider->getSettings($channel);
            if (null === $settings) {
                continue;
            }

            $io->section(sprintf('Brevo account of channel %s', (string) $channel->getCode()));

            $existing = array_map(
                static fn (Attribute $attribute): string => strtoupper($attribute->name),
                $this->attributesApi->all($settings->credentials),
            );

            $rows = [];
            foreach ($this->payloadBuilder->getAttributeTypes($channel) as $name => $type) {
                if (in_array($name, $existing, true)) {
                    $rows[] = [$name, $type, 'exists'];

                    continue;
                }

                if ($dryRun) {
                    $rows[] = [$name, $type, 'to create'];

                    continue;
                }

                try {
                    $this->attributesApi->create($settings->credentials, Attribute::CATEGORY_NORMAL, $name, $type);
                    $rows[] = [$name, $type, 'created'];
                } catch (BrevoException $exception) {
                    $rows[] = [$name, $type, 'failed: ' . $exception->getMessage()];
                    $failed = true;
                }
            }

            $this->accountAttributes->forget($settings->credentials);
            $io->table(['Attribute', 'Type', 'Status'], $rows);
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
