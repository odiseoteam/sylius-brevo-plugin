<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Newsletter\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Client\Api\ContactsApiInterface;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Newsletter\ConfirmationLinkInterface;
use Odiseo\SyliusBrevoPlugin\Newsletter\Message\RequestNewsletterConfirmation;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class RequestNewsletterConfirmationHandler
{
    /** @param ChannelRepositoryInterface<ChannelInterface> $channelRepository */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ContactsApiInterface $contactsApi,
        private readonly ConfirmationLinkInterface $confirmationLink,
    ) {
    }

    public function __invoke(RequestNewsletterConfirmation $message): void
    {
        $channel = $this->channelRepository->findOneByCode($message->getChannelCode());
        if (!$channel instanceof ChannelInterface) {
            return;
        }

        $settings = $this->configurationProvider->getSettings($channel);
        if (null === $settings || !$settings->hasNewsletter() || null === $settings->newsletterListId || null === $settings->doubleOptInTemplateId) {
            return;
        }

        $this->contactsApi->requestDoubleOptIn(
            $settings->credentials,
            $message->email,
            $settings->doubleOptInTemplateId,
            [$settings->newsletterListId],
            $this->confirmationLink->generate($channel, $message->email, $message->localeCode),
        );
    }
}
