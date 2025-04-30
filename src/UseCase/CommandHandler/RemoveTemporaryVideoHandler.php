<?php

namespace App\UseCase\CommandHandler;

use App\Repository\ClipRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Service\FileService;
use App\UseCase\Command\RemoveTemporaryVideo;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RemoveTemporaryVideoHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private ClipRepository $clipRepository,
        private VideoRepository $videoRepository,
        private FileService $fileService,
    ) {
    }

    public function __invoke(RemoveTemporaryVideo $message): void
    {
        $this->fileService->removeLocalFile(
            filePath: sprintf('public/tmp/%s/%s/%s', $message->userId->toString(), $message->clipId->toString(), $message->videoName),
        );
    }
}
