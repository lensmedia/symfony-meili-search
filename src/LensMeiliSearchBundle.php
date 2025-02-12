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

        $builder->getDefinition(LensMeiliSearch::class)
            ->setArgument('$clients', $config['clients'])
            ->setArgument('$groups', $config['groups']);

        $builder->registerForAutoconfiguration(LensMeiliSearchDocumentInterface::class)
            ->addTag(LensMeiliSearchDocumentInterface::class);

        $builder->registerForAutoconfiguration(LensMeiliSearchIndexInterface::class)
            ->addTag(LensMeiliSearchIndexInterface::class);
    }
}
