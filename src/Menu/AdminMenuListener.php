<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $brevo = $menu
            ->addChild('brevo')
            ->setLabel('odiseo_brevo.menu.admin.header')
            ->setLabelAttribute('icon', 'tabler:mail-forward')
        ;

        $brevo
            ->addChild('configuration', ['route' => 'odiseo_brevo_admin_channel_configuration_index', 'extras' => ['routes' => [
                ['route' => 'odiseo_brevo_admin_channel_configuration_create'],
                ['route' => 'odiseo_brevo_admin_channel_configuration_update'],
            ]]])
            ->setLabel('odiseo_brevo.menu.admin.configuration')
            ->setLabelAttribute('icon', 'tabler:settings')
        ;

        $this->placeAfter($menu, 'brevo', 'marketing');
    }

    /** Keeps the item at the end when the reference item doesn't exist. */
    private function placeAfter(ItemInterface $menu, string $name, string $reference): void
    {
        $order = array_keys($menu->getChildren());
        $position = array_search($reference, $order, true);
        if (false === $position) {
            return;
        }

        $order = array_values(array_diff($order, [$name]));
        array_splice($order, $position + 1, 0, [$name]);

        $menu->reorderChildren($order);
    }
}
