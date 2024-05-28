<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Exception;

use JsonSerializable;
use Lens\Bundle\MeiliSearchBundle\MeiliSearchNormalizerInterface;
use RuntimeException;
use Throwable;

class NormalizerNotFound extends RuntimeException implements MeiliSearchException
{
    public function __construct(mixed $object, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Normalizer for object "%s" not found. Create a "%s" service class, or implement "%s" or "%s" on the object.',
            get_debug_type($object),
            MeiliSearchNormalizerInterface::class,
            JsonSerializable::class,
            get_debug_type($object).'::__serialize',
        ), $code, $previous);
    }
}
