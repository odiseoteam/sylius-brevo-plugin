<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Controller\Shop;

use Odiseo\SyliusBrevoPlugin\Form\Type\NewsletterSubscriptionType;
use Odiseo\SyliusBrevoPlugin\Newsletter\NewsletterSubscriberInterface;
use Odiseo\SyliusBrevoPlugin\Newsletter\SubscriptionResult;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SubscribeToNewsletterAction
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly ChannelContextInterface $channelContext,
        private readonly NewsletterSubscriberInterface $subscriber,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $form = $this->formFactory->create(NewsletterSubscriptionType::class);
        $form->handleRequest($request);

        $email = $form->isSubmitted() && $form->isValid() ? $form->get('email')->getData() : null;
        $channel = $this->channelContext->getChannel();

        if (!is_string($email) || !$channel instanceof ChannelInterface) {
            NewsletterMessage::add($request, 'error', 'odiseo_brevo.newsletter.invalid_email');

            return $this->back($request);
        }

        // A bot: it gets the usual answer, nothing is subscribed.
        $honeypot = $form->get(NewsletterSubscriptionType::HONEYPOT)->getData();
        $result = is_string($honeypot) && '' !== $honeypot
            ? SubscriptionResult::Subscribed
            : $this->subscriber->subscribe($email, $channel, $request->getLocale());

        NewsletterMessage::add($request, 'success', SubscriptionResult::ConfirmationRequested === $result
            ? 'odiseo_brevo.newsletter.confirmation_sent'
            : 'odiseo_brevo.newsletter.subscribed');

        return $this->back($request);
    }

    /** Back to the form's page (only its path, never another host), scrolled to the form. */
    private function back(Request $request): RedirectResponse
    {
        $referer = (string) $request->headers->get('referer');
        $path = parse_url($referer, \PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return new RedirectResponse($this->urlGenerator->generate('sylius_shop_homepage') . NewsletterMessage::ANCHOR);
        }

        $query = parse_url($referer, \PHP_URL_QUERY);

        return new RedirectResponse($path . (is_string($query) ? '?' . $query : '') . NewsletterMessage::ANCHOR);
    }
}
