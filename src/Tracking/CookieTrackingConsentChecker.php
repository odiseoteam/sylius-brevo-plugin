<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking;

use Symfony\Component\HttpFoundation\Request;

/** Allowed unless a consent cookie is configured: then it must be present (with the value, if one is set). */
final class CookieTrackingConsentChecker implements TrackingConsentCheckerInterface
{
    public function __construct(
        private readonly ?string $cookieName = null,
        private readonly ?string $cookieValue = null,
    ) {
    }

    public function isAllowed(Request $request): bool
    {
        if (null === $this->cookieName) {
            return true;
        }

        $value = $request->cookies->get($this->cookieName);

        return null !== $value && (null === $this->cookieValue || $value === $this->cookieValue);
    }
}
