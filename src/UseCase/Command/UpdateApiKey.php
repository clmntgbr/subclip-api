<?php

namespace App\UseCase\Command;

use Symfony\Component\Uid\Uuid;

final class UpdateApiKey
{
    public function __construct(
        public readonly Uuid $userId,
        public readonly string $token,
    ) {
    }
}
