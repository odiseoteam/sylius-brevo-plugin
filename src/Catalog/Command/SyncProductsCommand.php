<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\Command;

use Odiseo\SyliusBrevoPlugin\Catalog\CatalogTargetResolverInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\ProductPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\ProductSenderInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\ProductVariantBatchesInterface;
use Odiseo\SyliusBrevoPlugin\Client\Api\EcommerceApi;
use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Client\Model\ProductData;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'odiseo:brevo:products:sync',
    description: 'Sends the product variants of the channels with the catalog module to Brevo.',
)]
final class SyncProductsCommand extends Command
{
    /** @param ChannelRepositoryInterface<ChannelInterface> $channelRepository */
    public function __construct(
        private readonly CatalogTargetResolverInterface $targetResolver,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ProductVariantBatchesInterface $variantBatches,
        private readonly ProductPayloadBuilderInterface $payloadBuilder,
        private readonly ProductSenderInterface $productSender,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Only the Brevo account of this channel code')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be sent')
            ->addOption('after-id', null, InputOption::VALUE_REQUIRED, 'Resume after this variant id', '0')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Send the variants unchanged since the last sync too')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $channelCode = $input->getOption('channel');
        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');
        $afterIdOption = $input->getOption('after-id');
        $afterId = is_numeric($afterIdOption) ? (int) $afterIdOption : 0;

        $accounts = array_map(
            static fn (array $channels): array => array_map(static fn (ChannelInterface $channel): string => (string) $channel->getCode(), $channels),
            array_filter(
                $this->targetResolver->channelsByAccount(),
                static fn (array $channels): bool => null === $channelCode || [] !== array_filter($channels, static fn (ChannelInterface $channel): bool => $channel->getCode() === $channelCode),
            ),
        );
        if ([] === $accounts) {
            $io->warning('No channel with an enabled Brevo configuration and the catalog module on.');

            return Command::SUCCESS;
        }

        foreach ($accounts as $channelCodes) {
            $io->section(sprintf('Brevo account of channel %s', $channelCodes[0]));
            $total = $this->variantBatches->count($channelCodes, $afterId);
            $created = $updated = $skipped = 0;

            foreach ($this->variantBatches->batches($channelCodes, $afterId, EcommerceApi::BATCH_SIZE) as $variants) {
                $channels = $this->channels($channelCodes);
                $settings = $this->configurationProvider->getSettings($channels[0]);
                if (null === $settings) {
                    break;
                }

                $products = array_map(fn (ProductVariantInterface $variant): ProductData => $this->payloadBuilder->build($variant, $this->channelFor($variant, $channels)), $variants);
                $changed = $force ? $products : $this->productSender->changed($settings->credentials, $products);
                $skipped += count($products) - count($changed);
                $lastId = end($variants)->getId();

                if ($dryRun) {
                    $io->table(['Id', 'Name', 'Price', 'Deleted'], array_map(
                        static fn (ProductData $product): array => [$product->id, $product->name, is_scalar($product->fields['price'] ?? null) ? (string) $product->fields['price'] : '', $product->deleted ? 'yes' : ''],
                        $changed,
                    ));

                    continue;
                }

                try {
                    $result = $this->productSender->send($settings->credentials, $changed, true);
                } catch (BrevoException $exception) {
                    $io->error(sprintf('%s Resume with --after-id=%d.', $exception->getMessage(), $afterId));

                    return Command::FAILURE;
                }

                $created += $result->created;
                $updated += $result->updated;
                $afterId = is_numeric($lastId) ? (int) $lastId : $afterId;
                $io->writeln(sprintf('Up to variant id %d.', $afterId));
            }

            $io->success($dryRun
                ? sprintf('%d variants, %d unchanged.', $total, $skipped)
                : sprintf('%d variants: %d created, %d updated, %d unchanged.', $total, $created, $updated, $skipped));
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $codes
     *
     * @return non-empty-list<ChannelInterface>
     */
    private function channels(array $codes): array
    {
        $channels = [];
        foreach ($codes as $code) {
            $channel = $this->channelRepository->findOneByCode($code);
            if ($channel instanceof ChannelInterface) {
                $channels[] = $channel;
            }
        }
        if ([] === $channels) {
            throw new \RuntimeException('The channels were removed during the sync.');
        }

        return $channels;
    }

    /** @param non-empty-list<ChannelInterface> $channels */
    private function channelFor(ProductVariantInterface $variant, array $channels): ChannelInterface
    {
        $product = $variant->getProduct();

        return $product instanceof ProductInterface ? $this->targetResolver->productChannel($product, $channels) : $channels[0];
    }
}
