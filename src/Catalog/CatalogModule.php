<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Catalog;

use Odiseo\SyliusBrevoPlugin\Module\ModuleInterface;

/** The channel's taxons (and products) in Brevo Ecommerce. */
final class CatalogModule implements ModuleInterface
{
    public const CODE = 'catalog';

    public function getCode(): string
    {
        return self::CODE;
    }

    public function getLabel(): string
    {
        return 'odiseo_brevo.module.catalog';
    }
}
