<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Command;

use Odiseo\SyliusBrevoPlugin\Catalog\CatalogTargetResolverInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\CategoryPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\ChannelTaxonsInterface;
use Odiseo\SyliusBrevoPlugin\Client\Api\EcommerceApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Model\CategoryData;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'odiseo:brevo:categories:sync',
    description: 'Sends the taxons of the channels with the catalog module to Brevo as categories.',
)]
final class SyncCategoriesCommand extends Command
{
    public function __construct(
        private readonly CatalogTargetResolverInterface $targetResolver,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ChannelTaxonsInterface $channelTaxons,
        private readonly CategoryPayloadBuilderInterface $payloadBuilder,
        private readonly EcommerceApiInterface $ecommerceApi,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Only the Brevo account of this channel code')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be sent')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $channelCode = $input->getOption('channel');
        $dryRun = (bool) $input->getOption('dry-run');

        $accounts = array_filter(
            $this->targetResolver->channelsByAccount(),
            static fn (array $channels): bool => null === $channelCode || [] !== array_filter($channels, static fn (ChannelInterface $channel): bool => $channel->getCode() === $channelCode),
        );
        if ([] === $accounts) {
            $io->warning('No channel with an enabled Brevo configuration and the catalog module on.');

            return Command::SUCCESS;
        }

        $failed = false;
        foreach ($accounts as $channels) {
            $settings = $this->configurationProvider->getSettings($channels[0]);
            if (null === $settings) {
                continue;
            }

            $io->section(sprintf('Brevo account of channel %s', (string) $channels[0]->getCode()));
            $categories = $this->categories($channels);

            if ($dryRun) {
                $io->table(['Id', 'Name', 'URL', 'Deleted'], array_map(
                    static fn (CategoryData $category): array => [$category->id, $category->name, $category->url ?? '', $category->deleted ? 'yes' : ''],
                    $categories,
                ));

                continue;
            }

            try {
                $result = $this->ecommerceApi->saveCategories($settings->credentials, $categories);
                $io->success(sprintf('%d categories created, %d updated.', $result->created, $result->updated));
            } catch (BrevoException $exception) {
                $io->error($exception->getMessage());
                $failed = true;
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Every channel's taxons, each built with the first channel that shows it.
     *
     * @param non-empty-list<ChannelInterface> $channels
     *
     * @return list<CategoryData>
     */
    private function categories(array $channels): array
    {
        $categories = [];
        foreach ($channels as $channel) {
            foreach ($this->channelTaxons->all($channel) as $taxon) {
                $categories[(string) $taxon->getCode()] ??= $this->payloadBuilder->build($taxon, $channel);
            }
        }

        return array_values($categories);
    }
}
