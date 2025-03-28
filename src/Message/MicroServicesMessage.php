<?php

namespace App\Message;

use App\Entity\Clip;

final class MicroServicesMessage
{
    public function __construct(
        public readonly Clip $clip,
        public readonly string $service,
    ) {
    }
}
