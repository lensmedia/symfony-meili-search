<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lens\Bundle\MeiliSearchBundle\MeiliSearch;
use Lens\Bundle\MeiliSearchBundle\MeiliSearchNormalizerInterface;
use Lens\Bundle\MeiliSearchBundle\MeiliSearchRepositoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(MeiliSearch::class)
        ->args([
            service(HttpClientInterface::class),
            tagged_iterator(MeiliSearchNormalizerInterface::class),
            abstract_arg('groups'),
            abstract_arg('uri'),
            abstract_arg('searchKey'),
            abstract_arg('adminKey'),
            abstract_arg('jsonEncodeOptions'),
        ])
        ->call('loadRepositories', [
            tagged_iterator(MeiliSearchRepositoryInterface::class)
        ])
    ;
};
