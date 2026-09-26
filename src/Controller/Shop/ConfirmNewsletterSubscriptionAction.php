<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Controller\Shop;

use Odiseo\SyliusBrevoPlugin\Newsletter\ConfirmationLinkInterface;
use Odiseo\SyliusBrevoPlugin\Newsletter\NewsletterSubscriberInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Where Brevo redirects after a double opt-in: the signed link proves the email. */
final class ConfirmNewsletterSubscriptionAction
{
    public function __construct(
        private readonly ConfirmationLinkInterface $confirmationLink,
        private readonly ChannelContextInterface $channelContext,
        private readonly NewsletterSubscriberInterface $subscriber,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $email = $request->query->getString('email');
        $channel = $this->channelContext->getChannel();
        $valid = '' !== $email && $this->confirmationLink->isValid($email, $request->query->getInt('expires'), $request->query->getString('token'));

        if ($valid && $channel instanceof ChannelInterface) {
            $this->subscriber->subscribe($email, $channel, $request->getLocale(), confirmed: true);
        }

        NewsletterMessage::add($request, $valid ? 'success' : 'error', $valid ? 'odiseo_brevo.newsletter.subscribed' : 'odiseo_brevo.newsletter.invalid_link');

        return new RedirectResponse($this->urlGenerator->generate('sylius_shop_homepage') . NewsletterMessage::ANCHOR);
    }
}
