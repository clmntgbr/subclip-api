<?php

namespace App\Message;

use Symfony\Component\Uid\Uuid;

final class TaskMessage
{
    public function __construct(
        public readonly Uuid $clipId,
        public readonly string $service,
    ) {
    }
}
