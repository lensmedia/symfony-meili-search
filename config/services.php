<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lens\Bundle\MeiliSearchBundle\Command\UpdateIndexes;
use Lens\Bundle\MeiliSearchBundle\LensMeiliSearch;
use Lens\Bundle\MeiliSearchBundle\LensMeiliSearchDocumentLoaderInterface;
use Lens\Bundle\MeiliSearchBundle\LensMeiliSearchIndexLoaderInterface;
use Psr\Http\Client\ClientInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(LensMeiliSearch::class)
        ->args([
            service(ClientInterface::class),
            abstract_arg('clients'),
            // abstract_arg('groups'),
        ])

        // Using calls so we can inject service classes that have a dependency on the MeiliSearch service
        // without causing a circular reference.
        ->call('initializeIndexLoaders', [
            tagged_iterator(LensMeiliSearchIndexLoaderInterface::class),
        ])
        ->call('registerDocumentLoaders', [
            tagged_iterator(LensMeiliSearchDocumentLoaderInterface::class),
        ])

        ->set(UpdateIndexes::class)
        ->args([
            service(LensMeiliSearch::class),
        ])
        ->tag('console.command')
    ;
};
