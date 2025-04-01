<?php

namespace App\UseCase\Command;

final class RemoveApiKey
{
    public function __construct(
        public readonly string $apiKey,
    ) {
    }
}
