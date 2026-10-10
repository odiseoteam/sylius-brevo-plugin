<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Diagnostics;

use Odiseo\SyliusBrevoPlugin\Configuration\BrevoSettings;
use Sylius\Component\Core\Model\ChannelInterface;

/** Adds rows to a channel in `odiseo:brevo:debug`. Tag: `odiseo_brevo.debug_info_provider`. */
interface DebugInfoProviderInterface
{
    /**
     * @param BrevoSettings|null $settings null when Brevo is off in the channel
     *
     * @return array<string, string> label => value; never secrets
     */
    public function provide(ChannelInterface $channel, ?BrevoSettings $settings): array;
}
