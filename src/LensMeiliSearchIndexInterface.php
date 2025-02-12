<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

interface LensMeiliSearchIndexInterface
{
    /**
     * @return \Lens\Bundle\MeiliSearchBundle\Index[]
     */
    public function getIndexes(): array;
}
