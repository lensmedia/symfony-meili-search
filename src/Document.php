<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use InvalidArgumentException;

readonly class Document
{
    public function __construct(public array $data = [])
    {
        if (empty($this->data)) {
            throw new InvalidArgumentException('The data array cannot be empty as they serve no purpose.');
        }
    }
}
