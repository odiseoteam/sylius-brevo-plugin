<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking;

use Odiseo\SyliusBrevoPlugin\Module\ModuleInterface;

/** The Brevo tracker in the channel's shop (page views, visitors identified by email) and the ecommerce events. */
final class TrackingModule implements ModuleInterface
{
    public const CODE = 'tracking';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getLabel(): string
    {
        return 'odiseo_brevo.module.tracking';
    }
}
