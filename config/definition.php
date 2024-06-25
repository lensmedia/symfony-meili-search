<?php

declare(strict_types=1);

use Lens\Bundle\MeiliSearchBundle\Indexes;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition
        ->rootNode()
            ->children()
                ->arrayNode('indexes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('prefix')->treatNullLike(Indexes::DEFAULT_OPTIONS['prefix'])->defaultNull()->end()
                        ->scalarNode('suffix')->treatNullLike(Indexes::DEFAULT_OPTIONS['suffix'])->defaultNull()->end()
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
