<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Exception;

use Lens\Bundle\MeiliSearchBundle\Index\Index;
use RuntimeException;
use Throwable;

class IndexNotFound extends RuntimeException implements MeiliSearchException
{
    public function __construct(Index|string $index, int $code = 0, ?Throwable $previous = null)
    {
        if ($index instanceof Index) {
            $index = $index->id;
        }

        parent::__construct(sprintf('MeiliSearch index "%s" not found.', $index), $code, $previous);
    }
}
