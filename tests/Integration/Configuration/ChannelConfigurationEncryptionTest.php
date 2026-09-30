<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Integration\Configuration;

use Doctrine\ORM\EntityManagerInterface;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfiguration;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ChannelConfigurationEncryptionTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();

        parent::tearDown();
    }

    public function testTheApiKeyStaysEncryptedAndIsOnlyDecryptedForTheCredentials(): void
    {
        $channel = new Channel();
        $channel->setCode('BREVO_TEST');
        $channel->setName('Brevo test');
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setDefaultLocale($this->persisted(new Locale(), 'en_US'));
        $channel->setBaseCurrency($this->persisted(new Currency(), 'USD'));
        $this->entityManager->persist($channel);

        $configuration = new ChannelConfiguration();
        $configuration->setChannel($channel);
        $configuration->setApiKey('xkeysib-secret');
        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        $stored = $this->storedApiKey($configuration);
        self::assertStringNotContainsString('xkeysib-secret', $stored);
        self::assertStringEndsWith('#ENCRYPTED', $stored);
        self::assertSame($stored, $configuration->getApiKey());

        $provider = self::getContainer()->get(ConfigurationProviderInterface::class);
        self::assertInstanceOf(ConfigurationProviderInterface::class, $provider);
        self::assertSame('xkeysib-secret', $provider->getCredentials($configuration)?->apiKey);

        // Not re-encrypted (and re-saved) on later flushes.
        $configuration->setSenderName('Shop');
        $this->entityManager->flush();
        self::assertSame($stored, $this->storedApiKey($configuration));

        $this->entityManager->clear();

        $channel = $this->entityManager->getRepository(Channel::class)->findOneBy(['code' => 'BREVO_TEST']);
        self::assertNotNull($channel);
        self::assertSame('xkeysib-secret', $provider->getSettings($channel)?->credentials->apiKey);
    }

    private function storedApiKey(ChannelConfiguration $configuration): string
    {
        $stored = $this->entityManager->getConnection()->fetchOne(
            'SELECT api_key FROM odiseo_brevo_channel_configuration WHERE id = ?',
            [$configuration->getId()],
        );
        self::assertIsString($stored);

        return $stored;
    }

    /**
     * @template T of Locale|Currency
     *
     * @param T $resource
     *
     * @return T
     */
    private function persisted(Locale|Currency $resource, string $code): Locale|Currency
    {
        $existing = $this->entityManager->getRepository($resource::class)->findOneBy(['code' => $code]);
        if (null !== $existing) {
            return $existing;
        }

        $resource->setCode($code);
        $this->entityManager->persist($resource);

        return $resource;
    }
}
