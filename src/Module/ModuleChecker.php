<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Module;

use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

final class ModuleChecker implements ModuleCheckerInterface
{
    public function __construct(
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ModuleRegistryInterface $moduleRegistry,
    ) {
    }

    public function isEnabled(ChannelInterface $channel, string $module): bool
    {
        if (!$this->moduleRegistry->has($module)) {
            return false;
        }

        return $this->configurationProvider->getSettings($channel)?->hasModule($module) ?? false;
    }
}
