<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Newsletter;

use Sylius\Component\Core\Model\ChannelInterface;

/** Signed shop URL that Brevo redirects to once a double opt-in is confirmed. */
interface ConfirmationLinkInterface
{
    public function generate(ChannelInterface $channel, string $email, string $localeCode): string;

    public function isValid(string $email, int $expires, string $token): bool;
}
