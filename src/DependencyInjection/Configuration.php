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
            ->end()
        ;

        return $treeBuilder;
    }
}
