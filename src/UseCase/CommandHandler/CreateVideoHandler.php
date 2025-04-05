<?php

namespace App\UseCase\CommandHandler;

use App\Entity\Video;
use App\Repository\VideoRepository;
use App\UseCase\Command\CreateVideo;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateVideoHandler
{
    public function __construct(
        private VideoRepository $videoRepository,
    ) {
    }

    public function __invoke(CreateVideo $message): void
    {
        $video = new Video(
            $message->videoId,
            $message->originalName,
            $message->name,
            $message->mimeType,
            $message->size,
        );

        $this->videoRepository->save($video);

        return;
    }
}
