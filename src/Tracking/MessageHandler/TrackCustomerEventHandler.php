<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventSenderInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Message\TrackCustomerEvent;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class TrackCustomerEventHandler
{
    /** @param ChannelRepositoryInterface<ChannelInterface> $channelRepository */
    public function __construct(
        private readonly RepositoryInterface $customerRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly TrackingEventSenderInterface $eventSender,
    ) {
    }

    public function __invoke(TrackCustomerEvent $message): void
    {
        $customer = $this->customerRepository->find($message->customerId);
        $channel = $this->channelRepository->findOneByCode($message->getChannelCode());
        if (!$customer instanceof CustomerInterface || !$channel instanceof ChannelInterface) {
            return;
        }

        $this->eventSender->send($message->eventCode, $customer, $channel, $customer);
    }
}
