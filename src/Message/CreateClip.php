<?php

namespace App\Message;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;

final class CreateClip
{
    public function __construct(
        public readonly Uuid $clipId,
        public readonly User $user,
    ) {
    }
}
