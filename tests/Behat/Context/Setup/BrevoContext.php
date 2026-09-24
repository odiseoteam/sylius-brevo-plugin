<?php

declare(strict_types=1);

namespace Tests\Odiseo\SyliusBrevoPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Doctrine\Persistence\ObjectManager;
use Odiseo\SyliusBrevoPlugin\Client\Http\BrevoResponse;
use Odiseo\SyliusBrevoPlugin\Contact\ContactPayloadBuilderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Odiseo\SyliusBrevoPlugin\Repository\ChannelConfigurationRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Tests\Odiseo\SyliusBrevoPlugin\Double\FakeBrevoHttpClient;
use Webmozart\Assert\Assert;

final class BrevoContext implements Context
{
    public function __construct(
        private readonly FactoryInterface $configurationFactory,
        private readonly ObjectManager $configurationManager,
        private readonly ChannelConfigurationRepositoryInterface $configurationRepository,
        private readonly FakeBrevoHttpClient $fakeBrevoHttpClient,
        private readonly ContactPayloadBuilderInterface $contactPayloadBuilder,
    ) {
    }

    /**
     * @Given /^the ("[^"]+" channel) has a Brevo configuration with the API key "([^"]+)"$/
     */
    public function theChannelHasABrevoConfiguration(ChannelInterface $channel, string $apiKey): void
    {
        /** @var ChannelConfigurationInterface $configuration */
        $configuration = $this->configurationFactory->createNew();
        $configuration->setChannel($channel);
        $configuration->setApiKey($apiKey);

        $this->configurationManager->persist($configuration);
        $this->configurationManager->flush();
    }

    /**
     * @Given /^the ("[^"]+" channel) has the "([^"]+)" Brevo module enabled$/
     */
    public function theChannelHasTheModuleEnabled(ChannelInterface $channel, string $module): void
    {
        $configuration = $this->configurationRepository->findOneByChannel($channel);
        Assert::notNull($configuration);

        $configuration->setModules([...$configuration->getModules(), $module]);
        $this->configurationManager->flush();
    }

    /**
     * @Given /^the ("[^"]+" channel) does not sync guest contacts$/
     */
    public function theChannelDoesNotSyncGuestContacts(ChannelInterface $channel): void
    {
        $configuration = $this->configurationRepository->findOneByChannel($channel);
        Assert::notNull($configuration);

        $configuration->setSyncingGuestContacts(false);
        $this->configurationManager->flush();
    }

    /**
     * @Given the Brevo account has every contact attribute
     */
    public function theBrevoAccountHasEveryContactAttribute(): void
    {
        $attributes = [];
        foreach ($this->contactPayloadBuilder->getAttributeTypes() as $name => $type) {
            $attributes[] = ['name' => $name, 'category' => 'normal', 'type' => $type];
        }

        $this->fakeBrevoHttpClient->respondAlways('GET', '/contacts/attributes', new BrevoResponse(200, ['attributes' => $attributes]));
    }

    /**
     * @Given Brevo is down
     */
    public function brevoIsDown(): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            $this->fakeBrevoHttpClient->failAll($method, new BrevoResponse(503, ['message' => 'Service unavailable']));
        }
    }

    /**
     * @Given the Brevo account is :email on the :plan plan
     */
    public function theBrevoAccountIs(string $email, string $plan): void
    {
        $this->fakeBrevoHttpClient->queue('GET', '/account', new BrevoResponse(200, [
            'email' => $email,
            'plan' => [['type' => $plan]],
        ]));
    }

    /**
     * @Given Brevo rejects the API key
     */
    public function brevoRejectsTheApiKey(): void
    {
        $this->fakeBrevoHttpClient->queue('GET', '/account', new BrevoResponse(401, [
            'code' => 'unauthorized',
            'message' => 'Key not found',
        ]));
    }
}
