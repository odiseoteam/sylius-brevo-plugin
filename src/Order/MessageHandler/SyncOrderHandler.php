<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Order\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Client\Api\EcommerceApiInterface;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Order\Message\SyncOrder;
use Odiseo\SyliusBrevoPlugin\Order\OrderPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Order\OrdersModule;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

final class SyncOrderHandler
{
    /** @param OrderRepositoryInterface<OrderInterface> $orderRepository */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly OrderPayloadBuilderInterface $payloadBuilder,
        private readonly EcommerceApiInterface $ecommerceApi,
    ) {
    }

    public function __invoke(SyncOrder $message): void
    {
        $order = $this->orderRepository->find($message->orderId);
        $channel = $order?->getChannel();
        if (!$order instanceof OrderInterface || !$channel instanceof ChannelInterface) {
            return;
        }

        $settings = $this->configurationProvider->getSettings($channel);
        $data = null === $settings || !$settings->hasModule(OrdersModule::CODE) ? null : $this->payloadBuilder->build($order);
        if (null !== $settings && null !== $data) {
            $this->ecommerceApi->saveOrder($settings->credentials, $data);
        }
    }
}
