<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lens\Bundle\MeiliSearchBundle\Command\UpdateIndexes;
use Lens\Bundle\MeiliSearchBundle\LensMeiliSearch;
use Lens\Bundle\MeiliSearchBundle\LensMeiliSearchDocumentInterface;
use Lens\Bundle\MeiliSearchBundle\LensMeiliSearchIndexInterface;
use Psr\Http\Client\ClientInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(LensMeiliSearch::class)
        ->args([
            service(ClientInterface::class),
            abstract_arg('clients'),
            // abstract_arg('groups'),
        ])
        ->call('registerIndexLoaders', [
            tagged_iterator(LensMeiliSearchIndexInterface::class),
        ])
        ->call('registerDocumentLoaders', [
            tagged_iterator(LensMeiliSearchDocumentInterface::class),
        ])

        ->set(UpdateIndexes::class)
        ->args([
            service(LensMeiliSearch::class),
        ])
        ->tag('console.command')
    ;
};
