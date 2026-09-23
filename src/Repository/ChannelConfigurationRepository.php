<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Repository;

use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;

class ChannelConfigurationRepository extends EntityRepository implements ChannelConfigurationRepositoryInterface
{
    public function findOneByChannel(ChannelInterface $channel): ?ChannelConfigurationInterface
    {
        /** @var ChannelConfigurationInterface|null $configuration */
        $configuration = $this->findOneBy(['channel' => $channel]);

        return $configuration;
    }
}
