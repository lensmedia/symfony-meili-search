<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use IteratorAggregate;
use ArrayAccess;
use Countable;

interface MeiliSearchInterface extends IteratorAggregate, ArrayAccess, Countable
{
    /** @param MeiliSearchRepositoryInterface[] $repositories */
    public function loadRepositories(array $repositories): void;

    public function loadRepository(MeiliSearchRepositoryInterface $repository): void;
}
