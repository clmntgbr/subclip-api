<?php

namespace App\Message;

final class CreateVideo
{
    public function __construct(
        public readonly string $originalName,
        public readonly string $name,
        public readonly string $mimeType,
        public readonly string $size,
    ) {
    }
}
