<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Index;

use Lens\Bundle\MeiliSearchBundle\MeiliSearchRepositoryInterface;

class Index
{
    public function __construct(
        public readonly string $id,
        public readonly array $context = [],
        public readonly string $primaryKey = 'id',
        public ?MeiliSearchRepositoryInterface $repository = null,
    ) {
    }
}
