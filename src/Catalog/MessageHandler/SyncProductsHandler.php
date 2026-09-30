<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Catalog\CatalogModule;
use Odiseo\SyliusBrevoPlugin\Catalog\Message\SyncProducts;
use Odiseo\SyliusBrevoPlugin\Catalog\ProductPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\ProductSenderInterface;
use Odiseo\SyliusBrevoPlugin\Client\Model\ProductData;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;

final class SyncProductsHandler
{
    /**
     * @param ChannelRepositoryInterface<ChannelInterface> $channelRepository
     * @param ProductVariantRepositoryInterface<ProductVariantInterface> $variantRepository
     */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ProductPayloadBuilderInterface $payloadBuilder,
        private readonly ProductSenderInterface $productSender,
    ) {
    }

    public function __invoke(SyncProducts $message): void
    {
        $channel = $this->channelRepository->findOneByCode($message->getChannelCode());
        if (!$channel instanceof ChannelInterface) {
            return;
        }

        $settings = $this->configurationProvider->getSettings($channel);
        if (null === $settings || !$settings->hasModule(CatalogModule::CODE)) {
            return;
        }

        $products = [];
        if ([] !== $message->variantCodes) {
            /** @var ProductVariantInterface $variant */
            foreach ($this->variantRepository->findBy(['code' => $message->variantCodes]) as $variant) {
                $products[] = $this->payloadBuilder->build($variant, $channel);
            }
        }

        foreach ($message->deletedVariants as $code => $name) {
            $products[] = new ProductData((string) $code, $name, deleted: true);
        }

        $this->productSender->send($settings->credentials, $products);
    }
}
