<?php

namespace App\UseCase\CommandHandler;

use App\Repository\ClipRepository;
use App\UseCase\Command\UpdateClipStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateClipStatusHandler
{
    public function __construct(
        private ClipRepository $clipRepository,
    ) {
    }

    public function __invoke(UpdateClipStatus $message): void
    {
    }
}
