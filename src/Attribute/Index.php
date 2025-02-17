<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Attribute;

use Attribute;
use Lens\Bundle\MeiliSearchBundle\LensMeiliSearch;

/**
 * Attributes are not implemented yet.
 *
 * This is just here to avoid future refactors.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Index
{
    public function __construct(
        public string $uid,
        public ?string $primaryKey = null,
        public array $settings = [],
        public string $client = LensMeiliSearch::DEFAULT_CLIENT,
        public array $context = [],
    ) {
    }
}
