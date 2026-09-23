<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Client\Exception;

/** Invalid API key, or the key lacks permission (401, 403). */
final class AuthenticationException extends BrevoException
{
}
