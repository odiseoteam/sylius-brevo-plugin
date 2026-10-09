<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Twig\Extension;

use Odiseo\SyliusBrevoPlugin\Catalog\CatalogModule;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventResolverInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\Event\TrackingEventSide;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackerIdentityStorageInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackingConsentCheckerInterface;
use Odiseo\SyliusBrevoPlugin\Tracking\TrackingModule;
use Psr\Log\LoggerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
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
        private readonly TrackingEventResolverInterface $eventResolver,
        private readonly LoggerInterface $logger,
        private readonly ProductVariantResolverInterface $variantResolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('odiseo_brevo_tracker', $this->tracker(...)),
            new TwigFunction('odiseo_brevo_tracking_event', $this->event(...)),
        ];
    }

    /**
     * The tracker of the current channel; null when it has no tracking module or client key.
     *
     * @return array{client_key: string, consent: bool, identify: array{email_id: string, ext_id?: string}|null}|null
     */
    public function tracker(): ?array
    {
        $channel = $this->currentChannel();
        $settings = null === $channel ? null : $this->configurationProvider->getSettings($channel);
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

    /**
     * A browser event for the tracker to push; null when the channel doesn't track it. With the catalog
     * module, also the Brevo Ecommerce product (default variant) or category viewed.
     *
     * @return array{name: string, properties: array<string, mixed>, view_product?: string, view_category?: string}|null
     */
    public function event(string $eventCode, ?object $subject): ?array
    {
        $channel = $this->currentChannel();
        $settings = null === $channel ? null : $this->configurationProvider->getSettings($channel);
        $event = $this->eventResolver->getEvents()[$eventCode] ?? null;
        if (null === $subject || null === $channel || TrackingEventSide::Browser !== $event?->getSide() || null === $settings?->trackerClientKey) {
            return null;
        }

        try {
            $resolved = $this->eventResolver->resolve($eventCode, $subject, $channel);
        } catch (\Throwable $exception) {
            // Never break the page for Brevo.
            $this->logger->error('Brevo tracking event could not be built', ['event' => $eventCode, 'error' => $exception->getMessage()]);

            return null;
        }
        if (null === $resolved) {
            return null;
        }

        $event = ['name' => $resolved->name, 'properties' => $resolved->properties];
        if ($settings->hasModule(CatalogModule::CODE)) {
            $productId = $subject instanceof ProductInterface ? $this->variantResolver->getVariant($subject)?->getCode() : null;
            $categoryId = $subject instanceof TaxonInterface ? $subject->getCode() : null;
            if (null !== $productId) {
                $event['view_product'] = $productId;
            }
            if (null !== $categoryId) {
                $event['view_category'] = $categoryId;
            }
        }

        return $event;
    }

    private function currentChannel(): ?ChannelInterface
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException) {
            return null;
        }

        return $channel instanceof ChannelInterface ? $channel : null;
    }
}
