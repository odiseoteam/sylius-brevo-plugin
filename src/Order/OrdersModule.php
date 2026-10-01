<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Order;

use Odiseo\SyliusBrevoPlugin\Module\ModuleInterface;

/** The channel's completed orders in Brevo Ecommerce. */
final class OrdersModule implements ModuleInterface
{
    public const CODE = 'orders';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getLabel(): string
    {
        return 'odiseo_brevo.module.orders';
    }
}
