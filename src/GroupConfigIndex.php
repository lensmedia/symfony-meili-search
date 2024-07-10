<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

readonly class GroupConfigIndex
{
    public function __construct(
        public string $index,
        public float $weight,
    ) {
    }
}
