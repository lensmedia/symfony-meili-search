<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

readonly class GroupConfig
{
    /** @var array<string, GroupConfigIndex> */
    public array $indexes;

    public function __construct(
        public string $name,
        array $indexes,
    ) {
        $idx = [];
        foreach ($indexes as $index) {
            $idx[$index['index']] = new GroupConfigIndex($index['index'], $index['weight'] ?? 1.0);
        }

        $this->indexes = $idx;
    }
}
