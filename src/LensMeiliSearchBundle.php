<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class LensMeiliSearchBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $builder->getDefinition(MeiliSearch::class)
            ->setArgument('$groups', $config['groups'])
            ->setArgument('$jsonEncodeOptions', $config['normalization']['json_encode_options'])
            ->setArgument('$uri', $config['uri'])
            ->setArgument('$searchKey', $config['search_key'])
            ->setArgument('$adminKey', $config['admin_key']);

        $builder->registerForAutoconfiguration(MeiliSearchRepositoryInterface::class)->addTag(MeiliSearchRepositoryInterface::class);
        $builder->registerForAutoconfiguration(MeiliSearchNormalizerInterface::class)->addTag(MeiliSearchNormalizerInterface::class);
    }
}
