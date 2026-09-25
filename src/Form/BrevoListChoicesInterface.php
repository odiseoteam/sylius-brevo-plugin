<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Form;

use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;

interface BrevoListChoicesInterface
{
    /**
     * Lists of the configuration's Brevo account, as choice label => id. Null when they can't be
     * loaded (no API key yet, Brevo unreachable).
     *
     * @return array<string, int>|null
     */
    public function forConfiguration(?ChannelConfigurationInterface $configuration): ?array;
}
