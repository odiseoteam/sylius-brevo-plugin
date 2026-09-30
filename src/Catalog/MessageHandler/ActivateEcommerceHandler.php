<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Catalog\EcommerceActivatorInterface;
use Odiseo\SyliusBrevoPlugin\Catalog\Message\ActivateEcommerce;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class ActivateEcommerceHandler
{
    /** @param ChannelRepositoryInterface<ChannelInterface> $channelRepository */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly EcommerceActivatorInterface $activator,
    ) {
    }

    public function __invoke(ActivateEcommerce $message): void
    {
        $channel = $this->channelRepository->findOneByCode($message->getChannelCode());
        if ($channel instanceof ChannelInterface) {
            $this->activator->activate($channel);
        }
    }
}
