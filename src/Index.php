<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

class Index
{
    public function __construct(
        public string $uid,
        public ?string $primaryKey = null,
        public array $settings = [],
        public string $client = LensMeiliSearch::DEFAULT_CLIENT,
    ) {
    }
}
