<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking\Event\Definition;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Webmozart\Assert\Assert;

/** The last item left the cart. */
final class CartDeleted extends AbstractOrderEvent
{
    public const CODE = 'cart_deleted';

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
