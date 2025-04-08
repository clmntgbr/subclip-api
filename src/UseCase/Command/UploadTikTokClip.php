<?php

namespace App\UseCase\Command;

use Symfony\Component\Uid\Uuid;

final class UploadTikTokClip
{
    public function __construct(
        public readonly Uuid $clipId,
        public readonly Uuid $socialAccountId,
    ) {
    }
}
