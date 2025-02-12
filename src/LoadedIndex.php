<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use Meilisearch\Endpoints\Indexes;
use Meilisearch\Client;

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
    ) {
    }
}
