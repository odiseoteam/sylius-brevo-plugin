<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Api\CommandHandler;

use Odiseo\SyliusBrevoPlugin\Api\Command\SubscribeToNewsletter;
use Odiseo\SyliusBrevoPlugin\Newsletter\NewsletterSubscriberInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class SubscribeToNewsletterHandler
{
    /** @param ChannelRepositoryInterface<ChannelInterface> $channelRepository */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly NewsletterSubscriberInterface $subscriber,
    ) {
    }

    public function __invoke(SubscribeToNewsletter $command): void
    {
        $channel = $this->channelRepository->findOneByCode($command->channelCode);
        if (!$channel instanceof ChannelInterface || null === $command->email) {
            return;
        }

        $this->subscriber->subscribe($command->email, $channel, $command->localeCode);
    }
}
