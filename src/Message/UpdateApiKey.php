<?php

namespace App\Message;

use App\Entity\User;

final class UpdateApiKey
{
    public function __construct(
        public readonly User $user,
        public readonly string $token,
    ) {
    }
}
