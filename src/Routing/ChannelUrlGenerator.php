<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Routing;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ChannelUrlGenerator implements ChannelUrlGeneratorInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CacheManager $imagineCacheManager,
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
        return $this->onChannelHost(
            $channel,
            fn (): string => $this->imagineCacheManager->getBrowserPath($path, $filter ?? $this->imageFilter),
        );
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
