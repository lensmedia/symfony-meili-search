<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->scalarNode('uri')->isRequired()->end()
            ->scalarNode('search_key')->isRequired()->end()
            ->scalarNode('admin_key')->defaultNull()->end()

            ->arrayNode('normalization')
                ->addDefaultsIfNotSet()
                ->children()
                    ->integerNode('json_encode_options')
                        ->defaultValue(0)
                    ->end()
                ->end()
            ->end()

            ->arrayNode('groups')
                ->defaultValue([])
                ->arrayPrototype()
                    ->scalarPrototype()->end()
                ->end()
            ->end()
    ;
};
