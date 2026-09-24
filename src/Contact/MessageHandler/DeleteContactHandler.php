<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Client\Api\ContactsApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactIdentifier;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactsModule;
use Odiseo\SyliusBrevoPlugin\Contact\Message\DeleteContact;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class DeleteContactHandler
{
    /** @param ChannelRepositoryInterface<ChannelInterface> $channelRepository */
    public function __construct(
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ContactsApiInterface $contactsApi,
    ) {
    }

    public function __invoke(DeleteContact $message): void
    {
        $channel = $this->channelRepository->findOneByCode($message->getChannelCode());
        $settings = $channel instanceof ChannelInterface ? $this->configurationProvider->getSettings($channel) : null;
        if (null === $settings || !$settings->hasModule(ContactsModule::CODE) || !$settings->deletingContactsOfRemovedCustomers) {
            return;
        }

        // Contacts created before the plugin have no ext_id yet.
        if (!$this->contactsApi->delete($settings->credentials, ContactIdentifier::extId($message->extId)) && null !== $message->email) {
            $this->contactsApi->delete($settings->credentials, ContactIdentifier::email($message->email));
        }
    }
}
