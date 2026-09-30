<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Routing;

use Sylius\Component\Core\Model\ChannelInterface;

/** Absolute URLs on the channel's hostname, regardless of the current request (CLI, workers). */
interface ChannelUrlGeneratorInterface
{
    /** @param array<string, mixed> $parameters */
    public function generate(ChannelInterface $channel, string $route, array $parameters = []): string;

    /** Image URL through a Liip Imagine filter (defaults to the configured one), generated if missing. */
    public function generateImageUrl(ChannelInterface $channel, string $path, ?string $filter = null): string;
}
