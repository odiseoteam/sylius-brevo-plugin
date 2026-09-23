<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Unit\Encryption;

use Odiseo\SyliusBrevoPlugin\Encryption\ChannelConfigurationEncrypter;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Payment\Encryption\EncrypterInterface;

final class ChannelConfigurationEncrypterTest extends TestCase
{
    private ChannelConfigurationEncrypter $encrypter;

    protected function setUp(): void
    {
        $this->encrypter = new ChannelConfigurationEncrypter(new class() implements EncrypterInterface {
            public function encrypt(string $data): string
            {
                return strrev($data) . self::ENCRYPTION_SUFFIX;
            }

            public function decrypt(string $data): string
            {
                return strrev(substr($data, 0, -self::ENCRYPTION_SUFFIX_LENGTH));
            }
        });
    }

    public function testItEncryptsAndDecryptsTheApiKey(): void
    {
        $configuration = $this->configuration('xkeysib-secret');

        $this->encrypter->encrypt($configuration);
        self::assertSame('terces-bisyekx#ENCRYPTED', $configuration->getApiKey());

        $this->encrypter->decrypt($configuration);
        self::assertSame('xkeysib-secret', $configuration->getApiKey());
    }

    public function testItNeverEncryptsTwice(): void
    {
        $configuration = $this->configuration('xkeysib-secret');

        $this->encrypter->encrypt($configuration);
        $this->encrypter->encrypt($configuration);

        self::assertSame('terces-bisyekx#ENCRYPTED', $configuration->getApiKey());
    }

    public function testItLeavesMissingAndPlainKeysAlone(): void
    {
        $empty = $this->configuration(null);
        $this->encrypter->encrypt($empty);
        $this->encrypter->decrypt($empty);
        self::assertNull($empty->getApiKey());

        $plain = $this->configuration('xkeysib-plain');
        $this->encrypter->decrypt($plain);
        self::assertSame('xkeysib-plain', $plain->getApiKey());
    }

    private function configuration(?string $apiKey): ChannelConfiguration
    {
        $configuration = new ChannelConfiguration();
        $configuration->setApiKey($apiKey);

        return $configuration;
    }
}
