<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Tracking;

use Odiseo\SyliusBrevoPlugin\Tracking\CookieTrackingConsentChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CookieTrackingConsentCheckerTest extends TestCase
{
    public function testWithoutACookieConfiguredTrackingIsAllowed(): void
    {
        self::assertTrue((new CookieTrackingConsentChecker())->isAllowed(new Request()));
    }

    public function testTheCookieMustBePresent(): void
    {
        $checker = new CookieTrackingConsentChecker('cookie_consent');

        self::assertFalse($checker->isAllowed(new Request()));
        self::assertTrue($checker->isAllowed(new Request(cookies: ['cookie_consent' => 'anything'])));
    }

    public function testTheCookieMustHaveTheValueWhenOneIsSet(): void
    {
        $checker = new CookieTrackingConsentChecker('cookie_consent', 'marketing');

        self::assertFalse($checker->isAllowed(new Request(cookies: ['cookie_consent' => 'necessary'])));
        self::assertTrue($checker->isAllowed(new Request(cookies: ['cookie_consent' => 'marketing'])));
    }
}
