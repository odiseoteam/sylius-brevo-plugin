<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Controller\Shop;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

/** Result shown inside the newsletter section, not among the page flashes. */
final class NewsletterMessage
{
    public const FLASH = 'odiseo_brevo_newsletter';

    public const ANCHOR = '#odiseo-newsletter';

    public static function add(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add(self::FLASH, ['type' => $type, 'message' => $message]);
        }
    }
}
