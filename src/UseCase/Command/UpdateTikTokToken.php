<?php

namespace App\UseCase\Command;

use Symfony\Component\Uid\Uuid;

final class UpdateTikTokToken
{
    public function __construct(
        public readonly Uuid $socialAccountId,
    ) {
    }
}
