<?php

declare(strict_types=1);

namespace Lens\Bundle\MeiliSearchBundle;

interface LensMeiliSearchDocumentInterface
{
    public function toDocument(object $data, array $context = []): LensMeiliSearchDocument;

    public function supports(): array;
}
