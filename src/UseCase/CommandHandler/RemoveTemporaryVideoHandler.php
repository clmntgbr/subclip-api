<?php

namespace App\UseCase\CommandHandler;

use App\Entity\Clip;
use App\Entity\Configuration;
use App\Entity\User;
use App\Entity\Video;
use App\Message\TaskMessage;
use App\Repository\ClipRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\Service\FileService;
use App\UseCase\Command\CreateClip;
use App\UseCase\Command\CreateVideo;
use App\UseCase\Command\RemoveTemporaryVideo;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

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
            filePath: sprintf('public/tmp/%s/%s/%s', $message->userId->__toString(), $message->clipId->__toString(), $message->videoName),
        );
    }
}
