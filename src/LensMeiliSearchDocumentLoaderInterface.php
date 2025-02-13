<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

interface LensMeiliSearchDocumentLoaderInterface
{
    public function toDocument(object $data, array $context = []): Document;

    public function supports(): array;
}
