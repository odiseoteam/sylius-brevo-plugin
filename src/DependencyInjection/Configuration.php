<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('odiseo_sylius_brevo');

        $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_url')
                            ->info('Brevo REST API base URL.')
                            ->defaultValue('https://api.brevo.com/v3')
                            ->cannotBeEmpty()
                        ->end()
                        ->floatNode('timeout')
                            ->info('Seconds to wait for a response.')
                            ->defaultValue(10.0)
                            ->min(1.0)
                        ->end()
                        ->integerNode('max_retries')
                            ->info('In-process retries for transient failures. Longer retries are handled by Messenger.')
                            ->defaultValue(2)
                            ->min(0)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('phone')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_region')
                            ->info('ISO 3166-1 alpha-2 fallback country for phone numbers without an international prefix, when neither the address nor the channel gives one.')
                            ->defaultNull()
                            ->validate()
                                ->ifTrue(static fn (mixed $value): bool => null !== $value && (!is_string($value) || 1 !== preg_match('/^[A-Za-z]{2}$/', $value)))
                                ->thenInvalid('Expected a two-letter country code, got %s.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('url')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('image_filter')
                            ->info('Liip Imagine filter for image URLs sent to Brevo.')
                            ->defaultValue('sylius_shop_product_large_thumbnail')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
