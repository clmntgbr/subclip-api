<?php

namespace App\Message;

final class RemoveApiKey
{
    public function __construct(
         public readonly string $apiKey,
    ) {
    }
}
