<?php

namespace App\UseCase\Command;

use Symfony\Component\Uid\Uuid;

final class RemoveTemporaryVideo
{
    public function __construct(
        public readonly string $videoName,
        public readonly Uuid $clipId,
        public readonly Uuid $userId,
    ) {
    }
}
