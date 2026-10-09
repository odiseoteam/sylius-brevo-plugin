<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Webmozart\Assert\Assert;

/** The cart got items, changed or got the customer's email. */
final class CartUpdated extends AbstractOrderEvent
{
    public const CODE = 'cart_updated';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getProperties(object $subject, ChannelInterface $channel): array
    {
        Assert::isInstanceOf($subject, OrderInterface::class);

        return $this->properties->forCart($subject, $channel);
    }
}
