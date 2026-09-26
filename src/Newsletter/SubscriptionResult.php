<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Newsletter;

enum SubscriptionResult
{
    case Subscribed;

    /** Brevo emailed a double opt-in link: subscribed once followed. */
    case ConfirmationRequested;
}
