<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

interface LensMeiliSearchIndexLoaderInterface extends LensMeiliSearchIndexInterface
{
    /**
     * @return \Lens\Bundle\MeiliSearchBundle\Attribute\Index[]
     */
    public function getIndexes(): array;
}
