<?php

namespace App\UseCase\CommandHandler;

use App\Entity\SocialAccount;
use App\Entity\Video;
use App\Exception\UploadTikTokClipException;
use App\Model\TikTok\PublishStatusTikTok;
use App\Protobuf\VideoPublishStatus;
use App\Repository\SocialAccountRepository;
use App\Repository\VideoRepository;
use App\Service\TikTokService;
use App\UseCase\Command\UpdateVideoPublishStatus;
use App\UseCase\Command\UploadTikTokVideoStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
final class UploadTikTokVideoStatusHandler
{
    public function __construct(
        private VideoRepository $videoRepository,
        private SocialAccountRepository $socialAccountRepository,
        private TikTokService $tikTokService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(UploadTikTokVideoStatus $message): void
    {
        try {
            /** @var ?Video $video */
            $video = $this->videoRepository->findOneBy(['id' => $message->videoId->__toString()]);

            if (null === $video) {
                throw new UploadTikTokClipException(sprintf('TikTok video does not exist with id [%s]', $message->videoId->__toString()));
            }

            /** @var ?SocialAccount $socialAccount */
            $socialAccount = $this->socialAccountRepository->findOneBy(['id' => $message->socialAccountId->__toString()]);

            if (null === $socialAccount) {
                throw new UploadTikTokClipException(sprintf('Social Account does not exist with id [%s]', $message->socialAccountId->__toString()));
            }

            $publishStatus = $this->tikTokService->getPublishStatus(
                socialAccount: $socialAccount,
                publishId: $video->getVideoPublish($socialAccount)?->getPublishId(),
            );

            if (in_array($publishStatus->getStatus(), [PublishStatusTikTok::FAILED, PublishStatusTikTok::PUBLISH_COMPLETE])) {
                $this->messageBus->dispatch(new UpdateVideoPublishStatus(
                    videoId: $video->getId(),
                    clipId: $message->clipId,
                    status: $publishStatus->getStatus(),
                    message: $publishStatus->getErrorMessage(),
                    socialAccountId: $socialAccount->getId(),
                ));

                return;
            }

            $this->messageBus->dispatch(new UploadTikTokVideoStatus(
                videoId: $video->getId(),
                socialAccountId: $socialAccount->getId(),
                clipId: $message->clipId,
                checkId: uniqid(),
            ), [new AmqpStamp('async'), new DelayStamp(20000)]);

            return;
        } catch (\Exception $exception) {
            $this->messageBus->dispatch(new UpdateVideoPublishStatus(
                videoId: $video->getId(),
                clipId: $message->clipId,
                status: VideoPublishStatus::name(VideoPublishStatus::ERROR),
                message: $exception->getMessage(),
                socialAccountId: $message->socialAccountId,
            ));
        }
    }
}
