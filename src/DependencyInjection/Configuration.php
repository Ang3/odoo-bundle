<?php

namespace Ang3\Bundle\OdooBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration.
 *
 * @author Joanis ROUANET
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}.
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ang3_odoo');

        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('default_connection')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->defaultValue('default')
                ->end()
                ->scalarNode('default_logger')
                    ->defaultNull()
                ->end()
                ->arrayNode('connections')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('url')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('database')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('user')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('password')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('logger')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('orm')
                    ->children()
                        ->arrayNode('managers')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->arrayNode('paths')
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->arrayNode('cache')
                                        ->children()
                                            ->scalarNode('schema')
                                                ->defaultValue('app_cache')
                                            ->end()
                                            ->scalarNode('schema')
                                                ->defaultValue('app_cache')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
