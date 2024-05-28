<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

use Lens\Bundle\MeiliSearchBundle\Index\Index;
use Lens\Bundle\MeiliSearchBundle\Index\IndexSettingsInterface;

interface MeiliSearchRepositoryInterface extends IndexSettingsInterface
{
    /** @return Index[] */
    public function indexes(): iterable;
}
