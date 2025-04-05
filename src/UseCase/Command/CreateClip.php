<?php

namespace App\UseCase\Command;

use Symfony\Component\Uid\Uuid;

final class CreateClip
{
    public function __construct(
        public readonly Uuid $clipId,
        public readonly Uuid $userId,
        public readonly string $originalName,
        public readonly string $name,
        public readonly string $mimeType,
        public readonly string $size,
    ) {
    }
}
