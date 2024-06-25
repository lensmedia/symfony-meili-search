<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition
        ->rootNode()
            ->children()
                ->arrayNode('indexes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('prefix')->defaultNull()->end()
                        ->scalarNode('suffix')->defaultNull()->end()
                    ->end()
                ->end()

                ->scalarNode('uri')->isRequired()->end()
                ->scalarNode('search_key')->isRequired()->end()
                ->scalarNode('admin_key')->defaultNull()->end()

                ->arrayNode('normalization')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('json_encode_options')->defaultValue(0)->end()
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
