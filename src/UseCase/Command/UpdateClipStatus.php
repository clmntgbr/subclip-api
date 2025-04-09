<?php

namespace App\UseCase\Command;

use Symfony\Component\Uid\Uuid;

final class UpdateClipStatus
{
    public function __construct(
        public readonly Uuid $clipId,
        public readonly string $status,
        public readonly ?string $message = null,
    ) {
    }
}
