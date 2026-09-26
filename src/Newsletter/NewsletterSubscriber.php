<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Newsletter;

use Doctrine\Persistence\ObjectManager;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Messenger\BrevoMessageDispatcherInterface;
use Odiseo\SyliusBrevoPlugin\Newsletter\Message\RequestNewsletterConfirmation;
use Sylius\Bundle\CoreBundle\Resolver\CustomerResolverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;

final class NewsletterSubscriber implements NewsletterSubscriberInterface
{
    public function __construct(
        private readonly CustomerResolverInterface $customerResolver,
        private readonly CustomerContextInterface $customerContext,
        private readonly ObjectManager $customerManager,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly BrevoMessageDispatcherInterface $dispatcher,
    ) {
    }

    public function subscribe(string $email, ChannelInterface $channel, string $localeCode, bool $confirmed = false): SubscriptionResult
    {
        $customer = $this->customerResolver->resolve($email);
        if ($customer->isSubscribedToNewsletter()) {
            return SubscriptionResult::Subscribed;
        }

        $confirmed = $confirmed || (null !== $customer->getId() && $this->customerContext->getCustomer() === $customer);
        $settings = $this->configurationProvider->getSettings($channel);
        if (!$confirmed && null !== $settings && $settings->hasNewsletter() && null !== $settings->doubleOptInTemplateId) {
            $this->dispatcher->dispatch(new RequestNewsletterConfirmation($settings->channelCode, (string) $customer->getEmail(), $localeCode));

            return SubscriptionResult::ConfirmationRequested;
        }

        $customer->setSubscribedToNewsletter(true);
        $this->customerManager->persist($customer);
        $this->customerManager->flush();

        return SubscriptionResult::Subscribed;
    }
}
