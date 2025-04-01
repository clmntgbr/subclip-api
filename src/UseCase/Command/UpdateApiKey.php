<?php

namespace App\UseCase\Command;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;

final class UpdateApiKey
{
    public function __construct(
        public readonly Uuid $userId,
        public readonly string $token,
    ) {
    }
}
