<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use Lens\Bundle\MeiliSearchBundle\Index\Index;

class Batch
{
    public function __construct(
        public readonly Index $index,
        public readonly string $primaryKey,
        public array $documents = [],
    ) {
    }
}
