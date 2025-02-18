<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->arrayNode('clients')
                ->info('List of meili search project names and urls.')
                ->useAttributeAsKey('name')
                ->requiresAtLeastOneElement()
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('url')->isRequired()->end()
                        ->scalarNode('key')
                            ->info('The private master key, if not provided only searches can be done (if search_key is provided).')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end()

            ->arrayNode('groups')
                ->info('Similar to multi-search, but made to merge results across multiple projects.')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('index')->isRequired()->end()
                        ->floatNode('weight')
                            ->defaultValue(1.0)
                            ->info('The ranking score is multiplied by this value for hits in this index.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
};
