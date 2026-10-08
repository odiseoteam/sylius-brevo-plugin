<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Tracking;

use Symfony\Component\HttpFoundation\Request;

/**
 * Whether the visitor allowed the Brevo tracker. Without consent the tracker waits for a
 * `brevo:consent` event on `document`, which the site's consent banner can dispatch.
 */
interface TrackingConsentCheckerInterface
{
    public function isAllowed(Request $request): bool;
}
