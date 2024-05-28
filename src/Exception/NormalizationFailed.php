<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Exception;

use RuntimeException;
use Throwable;

class NormalizationFailed extends RuntimeException implements MeiliSearchException
{
    public function __construct(mixed $object, Throwable $previous, ?int $code = null)
    {
        parent::__construct(sprintf(
            'Normalization for object "%s" failed: %s',
            get_debug_type($object),
            $previous->getMessage(),
        ), $code ?? $previous->getCode(), $previous);
    }
}
