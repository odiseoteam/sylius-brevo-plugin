<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Order\Payload;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * Adds Brevo order fields (billing, coupons, metaInfo...). Tag: `odiseo_brevo.order_payload_provider`.
 */
interface OrderPayloadProviderInterface
{
    /**
     * Null or "" means unknown: it's not sent. Later providers win on the same field, except
     * `metaInfo` and `billing`, merged by key.
     *
     * @return array<string, mixed>
     */
    public function provide(OrderInterface $order, ChannelInterface $channel): array;
}
