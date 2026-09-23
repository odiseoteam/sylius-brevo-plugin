<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Encryption;

use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Sylius\Component\Payment\Encryption\EncrypterInterface;
use Sylius\Component\Payment\Encryption\EncryptionAwareInterface;
use Sylius\Component\Payment\Encryption\EntityEncrypterInterface;

/**
 * Encrypts the API key with Sylius' payment encrypter (key at SYLIUS_PAYMENT_ENCRYPTION_KEY_PATH).
 *
 * @implements EntityEncrypterInterface<ChannelConfigurationInterface>
 */
final readonly class ChannelConfigurationEncrypter implements EntityEncrypterInterface
{
    public function __construct(
        private EncrypterInterface $encrypter,
    ) {
    }

    public function encrypt(EncryptionAwareInterface $resource): void
    {
        $apiKey = $resource->getApiKey();
        if (null === $apiKey || '' === $apiKey || $this->isEncrypted($apiKey)) {
            return;
        }

        $resource->setApiKey($this->encrypter->encrypt($apiKey));
    }

    public function decrypt(EncryptionAwareInterface $resource): void
    {
        $apiKey = $resource->getApiKey();
        if (null === $apiKey || !$this->isEncrypted($apiKey)) {
            return;
        }

        $resource->setApiKey($this->encrypter->decrypt($apiKey));
    }

    private function isEncrypted(string $value): bool
    {
        return str_ends_with($value, EncrypterInterface::ENCRYPTION_SUFFIX);
    }
}
