<?php

namespace App\Message;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;

final class CreateClip
{
    public function __construct(
        public readonly Uuid $clipId,
        public readonly User $user,
        public readonly string $originalName,
        public readonly string $name,
        public readonly string $mimeType,
        public readonly string $size,
    ) {
    }
}
