<?php

namespace App\UseCase\CommandHandler;

use App\Entity\Video;
use App\Entity\VideoPublish;
use App\Protobuf\VideoPublishStatus;
use App\Repository\VideoRepository;
use App\UseCase\Command\UpdateVideoStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateVideoStatusHandler
{
    public function __construct(
        private VideoRepository $videoRepository,
    ) {
    }

    public function __invoke(UpdateVideoStatus $message): void
    {
        /** @var ?Video $video */
        $video = $this->videoRepository->findOneBy(['id' => $message->videoId->__toString()]);

        if (null === $video) {
            throw new \Exception(sprintf('Video does not exist with id [%s]', $message->videoId->__toString()));
        }

        $videoPublish = new VideoPublish();
        if ($video->getVideoPublish()) {
            $videoPublish = $video->getVideoPublish();
        }

        if ($message->status === VideoPublishStatus::name(VideoPublishStatus::ERROR)) {
            $videoPublish->updateStatusError($message->message);
        }

        if ($message->status === VideoPublishStatus::name(VideoPublishStatus::PUBLISHED)) {
            $videoPublish->updateStatusPublished($message->message);
        }

        $video->setVideoPublish($videoPublish);
        $this->videoRepository->save($video);

        return;
    }
}
