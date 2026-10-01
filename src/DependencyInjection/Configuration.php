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
                        ->scalarNode('key')
                            ->info('Fallback API key for enabled channels without their own, e.g. %env(BREVO_API_KEY)%.')
                            ->defaultNull()
                        ->end()
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
                ->arrayNode('contacts')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('attributes')
                            ->info('Brevo attribute of each contact data key (first_name, total_spent...), or false to skip it. Unlisted keys go uppercase.')
                            ->example(['first_name' => 'NOMBRE', 'birthday' => false])
                            ->useAttributeAsKey('key')
                            ->variablePrototype()
                                ->validate()
                                    ->ifTrue(static fn (mixed $value): bool => !(false === $value || (is_string($value) && 1 === preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $value))))
                                    ->thenInvalid('Expected a Brevo attribute name or false, got %s.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('orders')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('statuses')
                            ->info('Brevo status of each order status (pending, paid, shipped, fulfilled, cancelled, refunded). Unlisted ones keep their name.')
                            ->example(['fulfilled' => 'completed'])
                            ->useAttributeAsKey('status')
                            ->scalarPrototype()->cannotBeEmpty()->end()
                            ->validate()
                                ->ifTrue(static fn (array $statuses): bool => [] !== array_diff(array_keys($statuses), ['pending', 'paid', 'shipped', 'fulfilled', 'cancelled', 'refunded']))
                                ->thenInvalid('Unknown order status in %s; expected pending, paid, shipped, fulfilled, cancelled or refunded.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('url')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('image_filter')
                            ->info('Liip Imagine filter for image URLs sent to Brevo; sylius_shop_product_original sends them as uploaded.')
                            ->defaultValue('odiseo_brevo_product')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
