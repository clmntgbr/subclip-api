<?php

namespace App\Message;

use App\Entity\Clip;

final class ServicesMessage
{
    public function __construct(
        public readonly Clip $clip,
        public readonly string $service,
    ) {
    }
}
