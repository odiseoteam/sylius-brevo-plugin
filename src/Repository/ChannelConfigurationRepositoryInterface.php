<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Repository;

use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ChannelConfigurationRepositoryInterface extends RepositoryInterface
{
    public function findOneByChannel(ChannelInterface $channel): ?ChannelConfigurationInterface;
}
