<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Module;

use Sylius\Component\Channel\Model\ChannelInterface;

interface ModuleCheckerInterface
{
    /** True when the module exists and is on for a channel with a usable configuration. */
    public function isEnabled(ChannelInterface $channel, string $module): bool;
}
