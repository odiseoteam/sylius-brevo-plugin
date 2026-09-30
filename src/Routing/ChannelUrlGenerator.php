<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Routing;

use Liip\ImagineBundle\Exception\ExceptionInterface as ImagineException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Service\FilterService;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ChannelUrlGenerator implements ChannelUrlGeneratorInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CacheManager $imagineCacheManager,
        private readonly FilterService $imagineFilterService,
        private readonly string $imageFilter,
    ) {
    }

    public function generate(ChannelInterface $channel, string $route, array $parameters = []): string
    {
        return $this->onChannelHost(
            $channel,
            fn (): string => $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL),
        );
    }

    public function generateImageUrl(ChannelInterface $channel, string $path, ?string $filter = null): string
    {
        $filter ??= $this->imageFilter;

        // Stored first, so the URL is the final one instead of Liip's redirecting "resolve" one.
        return $this->onChannelHost($channel, function () use ($path, $filter): string {
            try {
                return $this->imagineFilterService->getUrlOfFilteredImage($path, $filter);
            } catch (ImagineException) {
                return $this->imagineCacheManager->getBrowserPath($path, $filter);
            }
        });
    }

    /**
     * Router and Liip share the request context, so switching its host covers both.
     * The current context (request or `framework.router.default_uri`) is kept when the host matches,
     * so local setups keep their scheme and port; any other host is served over https.
     *
     * @param callable(): string $generate
     */
    private function onChannelHost(ChannelInterface $channel, callable $generate): string
    {
        $context = $this->urlGenerator->getContext();
        $hostname = $channel->getHostname();

        if (null === $hostname || '' === $hostname || strtolower($hostname) === strtolower($context->getHost())) {
            return $generate();
        }

        $previousHost = $context->getHost();
        $previousScheme = $context->getScheme();
        $previousHttpsPort = $context->getHttpsPort();

        $context->setHost($hostname);
        $context->setScheme('https');
        $context->setHttpsPort(443);

        try {
            return $generate();
        } finally {
            $context->setHost($previousHost);
            $context->setScheme($previousScheme);
            $context->setHttpsPort($previousHttpsPort);
        }
    }
}
