<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Form;

use Odiseo\SyliusBrevoPlugin\Client\Api\ListsApiInterface;
use Odiseo\SyliusBrevoPlugin\Client\Exception\BrevoException;
use Odiseo\SyliusBrevoPlugin\Configuration\ConfigurationProviderInterface;
use Odiseo\SyliusBrevoPlugin\Entity\ChannelConfigurationInterface;
use Symfony\Contracts\Service\ResetInterface;

/** Admin forms only: reads Brevo while rendering, once per account and request. */
final class BrevoListChoices implements BrevoListChoicesInterface, ResetInterface
{
    /** @var array<string, array<string, int>|null> */
    private array $loaded = [];

    public function __construct(
        private readonly ConfigurationProviderInterface $configurationProvider,
        private readonly ListsApiInterface $listsApi,
    ) {
    }

    public function forConfiguration(?ChannelConfigurationInterface $configuration): ?array
    {
        $credentials = null === $configuration ? null : $this->configurationProvider->getCredentials($configuration);
        if (null === $credentials) {
            return null;
        }

        $key = hash('xxh128', $credentials->apiKey);
        if (!array_key_exists($key, $this->loaded)) {
            try {
                $choices = [];
                foreach ($this->listsApi->lists($credentials) as $list) {
                    $choices[sprintf('%s (#%d)', $list->name, $list->id)] = $list->id;
                }
                $this->loaded[$key] = $choices;
            } catch (BrevoException) {
                $this->loaded[$key] = null;
            }
        }

        return $this->loaded[$key];
    }

    public function reset(): void
    {
        $this->loaded = [];
    }
}
