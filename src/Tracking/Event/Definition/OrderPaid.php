<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Webmozart\Assert\Assert;

/** The order got fully paid. */
final class OrderPaid extends AbstractOrderEvent
{
    public const CODE = 'order_paid';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getProperties(object $subject, ChannelInterface $channel): array
    {
        Assert::isInstanceOf($subject, OrderInterface::class);

        return $this->properties->forOrder($subject, $channel);
    }
}
