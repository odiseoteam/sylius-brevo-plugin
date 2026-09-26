<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Newsletter;

use Odiseo\SyliusBrevoPlugin\Routing\ChannelUrlGeneratorInterface;
use Sylius\Component\Core\Model\ChannelInterface;

final class ConfirmationLink implements ConfirmationLinkInterface
{
    public const ROUTE = 'odiseo_brevo_shop_newsletter_confirm';

    private const TTL = 30 * 86400;

    public function __construct(
        private readonly ChannelUrlGeneratorInterface $urlGenerator,
        #[\SensitiveParameter]
        private readonly string $secret,
    ) {
    }

    public function generate(ChannelInterface $channel, string $email, string $localeCode): string
    {
        $expires = time() + self::TTL;

        return $this->urlGenerator->generate($channel, self::ROUTE, [
            '_locale' => $localeCode,
            'email' => $email,
            'expires' => $expires,
            'token' => $this->token($email, $expires),
        ]);
    }

    public function isValid(string $email, int $expires, string $token): bool
    {
        return $expires >= time() && hash_equals($this->token($email, $expires), $token);
    }

    private function token(string $email, int $expires): string
    {
        return hash_hmac('sha256', sprintf('newsletter|%s|%d', mb_strtolower($email), $expires), $this->secret);
    }
}
