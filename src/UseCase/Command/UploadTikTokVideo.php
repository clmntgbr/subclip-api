<?php

namespace App\UseCase\Command;

use Symfony\Component\Uid\Uuid;

final class UploadTikTokVideo
{
    public function __construct(
        public readonly Uuid $clipId,
        public readonly Uuid $videoId,
        public readonly Uuid $socialAccountId,
        public readonly int $maxDuration,
        public readonly bool $areCommentsOff,
        public readonly bool $isDuetOff,
        public readonly bool $isStitchOff,
    ) {
    }
}
