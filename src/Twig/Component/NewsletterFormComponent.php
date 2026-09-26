<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Twig\Component;

use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Form\Type\NewsletterSubscriptionType;
use Odiseo\SyliusBrevoPlugin\Newsletter\NewsletterSubscriberInterface;
use Odiseo\SyliusBrevoPlugin\Newsletter\SubscriptionResult;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Shop newsletter section, where the newsletter is on. Subscribes without leaving the page; without
 * JavaScript the form posts to SubscribeToNewsletterAction instead.
 */
#[AsLiveComponent]
final class NewsletterFormComponent
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use HookableLiveComponentTrait;

    /** Result of the last subscription: flash translation key. */
    #[LiveProp]
    public ?string $result = null;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly ChannelContextInterface $channelContext,
        private readonly CustomerContextInterface $customerContext,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly NewsletterSubscriberInterface $subscriber,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function isAvailable(): bool
    {
        return null !== $this->channel() && true === $this->configurationProvider->getSettings($this->channel())?->hasNewsletter();
    }

    public function isSubscribed(): bool
    {
        return true === $this->customer()?->isSubscribedToNewsletter();
    }

    #[LiveAction]
    public function subscribe(): void
    {
        // Invalid: re-rendered with the errors.
        $this->submitForm();

        $channel = $this->channel();
        /** @var array{email?: mixed, website?: mixed} $data */
        $data = (array) $this->getForm()->getData();
        $email = $data['email'] ?? null;
        if (null === $channel || !is_string($email)) {
            return;
        }

        // A bot filled the honeypot: it gets the usual answer, nothing is subscribed.
        $honeypot = $data[NewsletterSubscriptionType::HONEYPOT] ?? null;
        $result = is_string($honeypot) && '' !== $honeypot
            ? SubscriptionResult::Subscribed
            : $this->subscriber->subscribe($email, $channel, $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en_US');

        $this->result = SubscriptionResult::ConfirmationRequested === $result
            ? 'odiseo_brevo.newsletter.confirmation_sent'
            : 'odiseo_brevo.newsletter.subscribed';
        $this->resetForm();
    }

    /** Keeps the email in sync on every keystroke without re-rendering, so Enter or autofill submit it too. */
    protected function getDataModelValue(): string
    {
        return 'norender|*';
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(NewsletterSubscriptionType::class, ['email' => $this->customer()?->getEmail()]);
    }

    private function channel(): ?ChannelInterface
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return null;
        }

        return $channel instanceof ChannelInterface ? $channel : null;
    }

    private function customer(): ?CustomerInterface
    {
        $customer = $this->customerContext->getCustomer();

        return $customer instanceof CustomerInterface ? $customer : null;
    }
}
