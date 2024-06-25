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
            ->setArgument('$uri', $this->option('uri', $config))
            ->setArgument('$groups', $this->option('groups', $config))
            ->setArgument('$searchKey', $this->option('search_key', $config))
            ->setArgument('$adminKey', $this->option('admin_key', $config))
            ->setArgument('$options', $config);

        $builder->registerForAutoconfiguration(MeiliSearchRepositoryInterface::class)->addTag(MeiliSearchRepositoryInterface::class);
        $builder->registerForAutoconfiguration(MeiliSearchNormalizerInterface::class)->addTag(MeiliSearchNormalizerInterface::class);
    }

    private function option(string $name, array &$config): mixed
    {
        $value = $config[$name] ?? null;
        unset($config[$name]);

        return $value;
    }
}
