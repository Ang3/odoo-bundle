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
                                ->defaultValue('%env(resolve:ODOO_API_URL)%')
                            ->end()
                            ->scalarNode('database')
                                ->defaultValue('%env(resolve:ODOO_API_DATABASE)%')
                            ->end()
                            ->scalarNode('user')
                                ->defaultValue('%env(resolve:ODOO_API_USERNAME)%')
                            ->end()
                            ->scalarNode('password')
                                ->defaultValue('%env(resolve:ODOO_API_PASSWORD)%')
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
