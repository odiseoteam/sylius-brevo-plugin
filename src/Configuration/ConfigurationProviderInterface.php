<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Configuration;

use Odiseo\SyliusBrevoPlugin\Client\Http\Credentials;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

interface ConfigurationProviderInterface
{
    /** Null when the channel has no enabled configuration or no API key to use. */
    public function getSettings(ChannelInterface $channel): ?BrevoSettings;

    /** The configuration's own key or the default one, enabled or not. */
    public function getCredentials(ChannelConfigurationInterface $configuration): ?Credentials;
}
