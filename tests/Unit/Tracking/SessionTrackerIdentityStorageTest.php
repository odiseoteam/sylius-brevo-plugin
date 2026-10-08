<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Tracking;

use Odiseo\SyliusBrevoPlugin\Tracking\SessionTrackerIdentityStorage;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Customer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class SessionTrackerIdentityStorageTest extends TestCase
{
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStack = new RequestStack();
        $this->requestStack->push($request);
    }

    public function testAGuestIsIdentifiedByEmailOnce(): void
    {
        $storage = new SessionTrackerIdentityStorage($this->requestStack);
        $customer = new Customer();
        $customer->setEmail('carrot@example.com');

        $storage->remember($customer);

        self::assertSame(['email_id' => 'carrot@example.com'], $storage->pull());
        self::assertNull($storage->pull());
    }

    public function testACustomerWithoutEmailIsNotRemembered(): void
    {
        $storage = new SessionTrackerIdentityStorage($this->requestStack);

        $storage->remember(new Customer());

        self::assertNull($storage->pull());
    }

    public function testWithoutASessionNothingIsRemembered(): void
    {
        $storage = new SessionTrackerIdentityStorage(new RequestStack());
        $customer = new Customer();
        $customer->setEmail('carrot@example.com');

        $storage->remember($customer);

        self::assertNull($storage->pull());
    }
}
