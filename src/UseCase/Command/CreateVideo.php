<?php

namespace App\UseCase\Command;

use Symfony\Component\Uid\Uuid;

final class CreateVideo
{
    public function __construct(
        public readonly Uuid $videoId,
        public readonly string $originalName,
        public readonly string $name,
        public readonly string $mimeType,
        public readonly string $size,
    ) {
    }
}
