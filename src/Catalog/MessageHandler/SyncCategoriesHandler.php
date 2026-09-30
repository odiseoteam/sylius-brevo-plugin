<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Catalog\CatalogModule;
use Odiseo\SyliusBrevoPlugin\Catalog\CategoryPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\Message\SyncCategories;
use Odiseo\SyliusBrevoPlugin\Client\Api\EcommerceApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Model\CategoryData;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

final class SyncCategoriesHandler
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     * @param TaxonRepositoryInterface<TaxonInterface> $taxonRepository
     */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly TaxonRepositoryInterface $taxonRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly CategoryPayloadBuilderInterface $payloadBuilder,
        private readonly EcommerceApiInterface $ecommerceApi,
    ) {
    }

    public function __invoke(SyncCategories $message): void
    {
        $channel = $this->channelRepository->findOneByCode($message->getChannelCode());
        if (!$channel instanceof ChannelInterface) {
            return;
        }

        $settings = $this->configurationProvider->getSettings($channel);
        if (null === $settings || !$settings->hasModule(CatalogModule::CODE)) {
            return;
        }

        $categories = [];
        foreach ($message->taxonCodes as $code) {
            $taxon = $this->taxonRepository->findOneBy(['code' => $code]);
            if ($taxon instanceof TaxonInterface) {
                $categories[] = $this->payloadBuilder->build($taxon, $channel);
            }
        }

        foreach ($message->deletedTaxons as $code => $name) {
            $categories[] = new CategoryData((string) $code, $name, deleted: true);
        }

        if ([] !== $categories) {
            $this->ecommerceApi->saveCategories($settings->credentials, $categories);
        }
    }
}
