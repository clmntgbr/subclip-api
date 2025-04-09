<?php

namespace App\UseCase\Command;

use Symfony\Component\Uid\Uuid;

final class UpdateVideoStatus
{
    public function __construct(
        public readonly Uuid $videoId,
        public readonly Uuid $socialAccountId,
        public readonly string $status,
        public readonly ?string $message = null,
    ) {
    }
}
