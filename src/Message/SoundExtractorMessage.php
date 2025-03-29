<?php

namespace App\Message;

use App\Protobuf\ApiMessage;

final class SoundExtractorMessage
{
    public function __construct(
        public readonly ApiMessage $apiMessage,
    ) {
    }
}
