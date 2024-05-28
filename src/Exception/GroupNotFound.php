<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Exception;

use RuntimeException;
use Throwable;

class GroupNotFound extends RuntimeException implements MeiliSearchException
{
    public function __construct(string $group, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('MeiliSearch group "%s" not found.', $group), $code, $previous);
    }
}
