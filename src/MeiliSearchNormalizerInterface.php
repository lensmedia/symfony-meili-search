<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

interface MeiliSearchNormalizerInterface
{
    public function normalize(object $object, array $context): array;

    public function supportsNormalization(object $object, array $context): bool;
}
