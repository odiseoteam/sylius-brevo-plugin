<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Encryption;

use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Sylius\Component\Payment\Encryption\EncryptionAwareInterface;
use Sylius\Component\Payment\Encryption\EntityEncrypterInterface;

/**
 * Encrypts the API key when the configuration is saved. It's never decrypted in the entity:
 * ConfigurationProvider does it when building the credentials.
 *
 * @implements EntityEncrypterInterface<ChannelConfigurationInterface>
 */
final readonly class ChannelConfigurationEncrypter implements EntityEncrypterInterface
{
    public function __construct(private ApiKeyEncrypterInterface $apiKeyEncrypter)
    {
    }

    public function encrypt(EncryptionAwareInterface $resource): void
    {
        $apiKey = $resource->getApiKey();
        if (null !== $apiKey) {
            $resource->setApiKey($this->apiKeyEncrypter->encrypt($apiKey));
        }
    }

    public function decrypt(EncryptionAwareInterface $resource): void
    {
        $apiKey = $resource->getApiKey();
        if (null !== $apiKey) {
            $resource->setApiKey($this->apiKeyEncrypter->decrypt($apiKey));
        }
    }
}
