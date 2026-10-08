<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Twig\Extension;

use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackerIdentityStorageInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackingConsentCheckerInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackingModule;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TrackingExtension extends AbstractExtension
{
    public function __construct(
        private readonly ChannelContextInterface $channelContext,
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly TrackingConsentCheckerInterface $consentChecker,
        private readonly TrackerIdentityStorageInterface $identityStorage,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('odiseo_brevo_tracker', $this->tracker(...)),
        ];
    }

    /**
     * The tracker of the current channel; null when it has no tracking module or client key.
     *
     * @return array{client_key: string, consent: bool, identify: array{email_id: string, ext_id?: string}|null}|null
     */
    public function tracker(): ?array
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return null;
        }

        $settings = $channel instanceof ChannelInterface ? $this->configurationProvider->getSettings($channel) : null;
        $request = $this->requestStack->getCurrentRequest();
        if (null === $settings || !$settings->hasModule(TrackingModule::CODE) || null === $settings->trackerClientKey || null === $request) {
            return null;
        }

        return [
            'client_key' => $settings->trackerClientKey,
            'consent' => $this->consentChecker->isAllowed($request),
            'identify' => $this->identityStorage->pull(),
        ];
    }
}
