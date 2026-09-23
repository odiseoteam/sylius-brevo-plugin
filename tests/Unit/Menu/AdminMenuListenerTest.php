<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuFactory;
use Odiseo\SyliusBrevoPlugin\Menu\AdminMenuListener;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListenerTest extends TestCase
{
    private FactoryInterface $factory;

    protected function setUp(): void
    {
        $this->factory = new MenuFactory();
    }

    public function testItAddsBrevoRightAfterMarketing(): void
    {
        $menu = $this->factory->createItem('root');
        foreach (['catalog', 'marketing', 'configuration'] as $name) {
            $menu->addChild($name);
        }

        (new AdminMenuListener())->addAdminMenuItems(new MenuBuilderEvent($this->factory, $menu));

        self::assertSame(['catalog', 'marketing', 'brevo', 'configuration'], array_keys($menu->getChildren()));
        self::assertNotNull($menu->getChild('brevo')?->getChild('configuration'));
    }

    public function testItAddsBrevoAtTheEndWithoutMarketing(): void
    {
        $menu = $this->factory->createItem('root');
        $menu->addChild('catalog');

        (new AdminMenuListener())->addAdminMenuItems(new MenuBuilderEvent($this->factory, $menu));

        self::assertSame(['catalog', 'brevo'], array_keys($menu->getChildren()));
    }
}
