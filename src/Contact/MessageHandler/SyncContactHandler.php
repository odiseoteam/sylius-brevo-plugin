<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Contact\MessageHandler;

use Odiseo\SyliusBrevoPlugin\Client\Api\ContactsApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\NotFoundException;
use Odiseo\SyliusBrevoPlugin\Client\Model\ContactIdentifier;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\AccountAttributesInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactLists;
use Odiseo\SyliusBrevoPlugin\Contact\ContactPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Contact\ContactsModule;
use Odiseo\SyliusBrevoPlugin\Contact\Message\SyncContact;
use Psr\Log\LoggerInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Updates the contact by ext_id first, so an email change keeps the same contact; creates it
 * (or links an existing one by email) when Brevo doesn't know that ext_id yet.
 */
final class SyncContactHandler
{
    /** @param ChannelRepositoryInterface<ChannelInterface> $channelRepository */
    public function __construct(
        private readonly RepositoryInterface $customerRepository,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ContactPayloadBuilderInterface $payloadBuilder,
        private readonly AccountAttributesInterface $accountAttributes,
        private readonly ContactsApiInterface $contactsApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SyncContact $message): void
    {
        $customer = $this->customerRepository->find($message->customerId);
        $channel = $this->channelRepository->findOneByCode($message->getChannelCode());
        if (!$customer instanceof CustomerInterface || !$channel instanceof ChannelInterface || null === $customer->getEmail()) {
            return;
        }

        $settings = $this->configurationProvider->getSettings($channel);
        if (null === $settings || !$settings->hasModule(ContactsModule::CODE)) {
            return;
        }

        $data = $this->payloadBuilder->build($customer, $channel);
        $existing = $this->accountAttributes->names($settings->credentials);

        $missing = array_diff(array_keys($data->attributes), array_map('strtoupper', $existing));
        if ([] !== $missing) {
            $this->logger->warning('Brevo contact attributes missing in the account; run odiseo:brevo:attributes:setup', [
                'channel' => $message->getChannelCode(),
                'attributes' => array_values($missing),
            ]);
        }

        $data = $data->withAttributesIn($existing)->withListIds(ContactLists::of($customer, $settings));

        // Only an actual unsubscription leaves the list: people may have joined it from Brevo.
        $newsletterListId = $settings->hasNewsletter() ? $settings->newsletterListId : null;
        if ($message->leftNewsletter && null !== $newsletterListId && !$customer->isSubscribedToNewsletter() && $newsletterListId !== $settings->customersListId) {
            $data = $data->withUnlinkListIds([$newsletterListId]);
        }

        try {
            $this->contactsApi->update($settings->credentials, ContactIdentifier::extId((string) $data->extId), $data);
        } catch (NotFoundException) {
            $this->contactsApi->upsert($settings->credentials, $data);
        }
    }
}
