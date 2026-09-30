<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Encryption;

use Sylius\Component\Payment\Encryption\EncrypterInterface;

/** Uses Sylius' payment encrypter (key at SYLIUS_PAYMENT_ENCRYPTION_KEY_PATH). */
final readonly class ApiKeyEncrypter implements ApiKeyEncrypterInterface
{
    public function __construct(private EncrypterInterface $encrypter)
    {
    }

    public function encrypt(#[\SensitiveParameter] string $apiKey): string
    {
        return '' === $apiKey || $this->isEncrypted($apiKey) ? $apiKey : $this->encrypter->encrypt($apiKey);
    }

    public function decrypt(string $apiKey): string
    {
        return $this->isEncrypted($apiKey) ? $this->encrypter->decrypt($apiKey) : $apiKey;
    }

    private function isEncrypted(string $value): bool
    {
        return str_ends_with($value, EncrypterInterface::ENCRYPTION_SUFFIX);
    }
}
