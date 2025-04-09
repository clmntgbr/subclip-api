<?php

namespace App\UseCase\CommandHandler;

use App\Entity\SocialAccount;
use App\Entity\Video;
use App\Entity\VideoPublish;
use App\Protobuf\VideoPublishStatus;
use App\Repository\SocialAccountRepository;
use App\Repository\VideoRepository;
use App\UseCase\Command\UpdateVideoStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateVideoStatusHandler
{
    public function __construct(
        private VideoRepository $videoRepository,
        private SocialAccountRepository $socialAccountRepository,
    ) {
    }

    public function __invoke(UpdateVideoStatus $message): void
    {
        /** @var ?Video $video */
        $video = $this->videoRepository->findOneBy(['id' => $message->videoId->__toString()]);

        if (null === $video) {
            throw new \Exception(sprintf('Video does not exist with id [%s]', $message->videoId->__toString()));
        }

        /** @var ?SocialAccount $socialAccount */
        $socialAccount = $this->socialAccountRepository->findOneBy(['id' => $message->socialAccountId->__toString()]);

        if (null === $socialAccount) {
            throw new \Exception(sprintf('Social account does not exist with id [%s]', $message->socialAccountId->__toString()));
        }

        $videoPublish = new VideoPublish($video, $socialAccount);
        if ($video->getVideoPublish($socialAccount)) {
            $videoPublish = $video->getVideoPublish($socialAccount);
        }

        if ($message->status === VideoPublishStatus::name(VideoPublishStatus::ERROR)) {
            $videoPublish->updateStatusError($message->message);
        }

        if ($message->status === VideoPublishStatus::name(VideoPublishStatus::PUBLISHED)) {
            $videoPublish->updateStatusPublished($message->message);
        }

        $video->addVideoPublish($videoPublish);
        $this->videoRepository->save($video);

        return;
    }
}
