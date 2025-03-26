<?php

namespace App\MessageHandler;

use App\Entity\Video;
use App\Message\CreateVideo;
use App\Repository\VideoRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateVideoHandler
{
    public function __construct(
        private VideoRepository $videoRepository,
    ) {
    }

    public function __invoke(CreateVideo $message): Video
    {
        $video = new Video(
            $message->originalName,
            $message->name,
            $message->mimeType,
            $message->size,
        );

        $this->videoRepository->save($video);

        return $video;
    }
}
