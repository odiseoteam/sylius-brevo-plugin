<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Client\Model\ProductData;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

interface ProductPayloadBuilderInterface
{
    /** One Brevo product per variant; deleted when the channel doesn't sell it. */
    public function build(ProductVariantInterface $variant, ChannelInterface $channel): ProductData;
}
