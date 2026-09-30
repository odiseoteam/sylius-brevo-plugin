<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Encryption;

/** API keys are stored encrypted and only decrypted to call Brevo. Both ways are idempotent. */
interface ApiKeyEncrypterInterface
{
    public function encrypt(string $apiKey): string;

    public function decrypt(string $apiKey): string;
}
