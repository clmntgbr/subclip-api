<?php

namespace App\UseCase\CommandHandler;

use App\Entity\SocialAccount;
use App\Entity\Video;
use App\Model\TikTok\PublishStatusTikTok;
use App\Protobuf\VideoPublishStatus;
use App\Repository\SocialAccountRepository;
use App\Repository\VideoPublishRepository;
use App\Repository\VideoRepository;
use App\UseCase\Command\RemoveTemporaryVideo;
use App\UseCase\Command\UpdateVideoPublishStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class UpdateVideoPublishStatusHandler
{
    public function __construct(
        private VideoRepository $videoRepository,
        private SocialAccountRepository $socialAccountRepository,
        private VideoPublishRepository $videoPublishRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(UpdateVideoPublishStatus $message): void
    {
        /** @var ?Video $video */
        $video = $this->videoRepository->findOneBy(['id' => $message->videoId->toString()]);

        if (null === $video) {
            throw new \Exception(sprintf('Video does not exist with id [%s]', $message->videoId->toString()));
        }

        /** @var ?SocialAccount $socialAccount */
        $socialAccount = $this->socialAccountRepository->findOneBy(['id' => $message->socialAccountId->toString()]);

        if (null === $socialAccount) {
            throw new \Exception(sprintf('Social account does not exist with id [%s]', $message->socialAccountId->toString()));
        }

        $videoPublish = $this->videoPublishRepository->createOrUpdate([
            'video' => $video,
            'socialAccount' => $socialAccount,
        ], [
            'video' => $video,
            'socialAccount' => $socialAccount,
        ]);

        if ($video->getVideoPublish($socialAccount)) {
            $videoPublish = $video->getVideoPublish($socialAccount);
        }

        if (in_array($message->status, [PublishStatusTikTok::FAILED, VideoPublishStatus::name(VideoPublishStatus::ERROR)])) {
            $videoPublish->updateStatusError($message->message);
        }

        if (PublishStatusTikTok::PUBLISH_COMPLETE === $message->status) {
            $videoPublish->updateStatusPublished($message->message);
        }

        if (in_array($message->status, [PublishStatusTikTok::PUBLISH_COMPLETE, PublishStatusTikTok::FAILED, VideoPublishStatus::name(VideoPublishStatus::ERROR)])) {
            $this->messageBus->dispatch(new RemoveTemporaryVideo(
                videoName: $video->getName(),
                clipId: $message->clipId,
                userId: $socialAccount->getUser()->getId(),
            ));
        }

        $video->addVideoPublish($videoPublish);
        $this->videoRepository->save($video);

        return;
    }
}
