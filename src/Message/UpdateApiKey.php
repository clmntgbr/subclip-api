<?php

namespace App\Message;

use App\Entity\User;
use App\Entity\ValueObject\Token;

final class UpdateApiKey
{
    public function __construct(
        public readonly User $user,
        public readonly string $token,
    ) {
    }
}
