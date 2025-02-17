<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use Lens\Bundle\MeiliSearchBundle\Attribute\Index;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;

/**
 * Internal class to track associations with config uid and client for the moment when we do want to load the remote entry.
 *
 * @internal
 */
class LoadedIndex
{
    public function __construct(
        public Index $config,
        public Client $client,
        public ?Indexes $remote = null,
        public array $context = [],
    ) {
    }
}
