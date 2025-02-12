<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle\Exception;

use InvalidArgumentException;

class InvalidTransformData extends InvalidArgumentException implements LensMeiliSearchExceptionInterface
{
    public function __construct(mixed $data, string $expected)
    {
        $message = sprintf('Invalid data type %s, expected %s', get_debug_type($data), $expected);

        parent::__construct($message);
    }
}
