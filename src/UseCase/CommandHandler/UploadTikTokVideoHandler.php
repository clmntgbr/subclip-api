<?php

namespace App\UseCase\CommandHandler;

use App\Entity\Clip;
use App\Entity\SocialAccount;
use App\Entity\Video;
use App\Exception\UploadTikTokClipException;
use App\Protobuf\VideoPublishStatus;
use App\Repository\ClipRepository;
use App\Repository\SocialAccountRepository;
use App\Repository\VideoPublishRepository;
use App\Repository\VideoRepository;
use App\Service\FileService;
use App\Service\TikTokService;
use App\UseCase\Command\UpdateVideoPublishStatus;
use App\UseCase\Command\UploadTikTokVideo;
use App\UseCase\Command\UploadTikTokVideoStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
final class UploadTikTokVideoHandler
{
    public function __construct(
        private ClipRepository $clipRepository,
        private VideoRepository $videoRepository,
        private VideoPublishRepository $videoPublishRepository,
        private SocialAccountRepository $socialAccountRepository,
        private TikTokService $tikTokService,
        private MessageBusInterface $messageBus,
        private FileService $fileService,
    ) {
    }

    public function __invoke(UploadTikTokVideo $message): void
    {
        try {
            /** @var ?Clip $clip */
            $clip = $this->clipRepository->findOneBy(['id' => $message->clipId->toString()]);

            if (null === $clip) {
                throw new UploadTikTokClipException(message: 'TikTokClip does not exist with id '.$message->clipId->toString());
            }

            /** @var ?Video $video */
            $video = $this->videoRepository->findOneBy(['id' => $message->videoId->toString()]);

            if (null === $video) {
                throw new UploadTikTokClipException(message: 'TikTok video does not exist with id '.$message->videoId->toString());
            }

            /** @var ?SocialAccount $socialAccount */
            $socialAccount = $this->socialAccountRepository->findOneBy(['id' => $message->socialAccountId->toString()]);

            if (null === $socialAccount) {
                throw new UploadTikTokClipException(message: 'Social Account does not exist with id '.$message->socialAccountId->toString());
            }

            if ($video->getLength() > $message->maxDuration) {
                throw new UploadTikTokClipException(username: $socialAccount->getUsername(), message: 'The video is too long. Max length is '.$video->getLength().' seconds');
            }

            $localPath = $this->fileService->downloadFromS3(
                userId: $clip->getUser()->getId(),
                clipId: $clip->getId(),
                fileName: $clip->getProcessedVideo()->getName()
            );

            $publishInfoTikTok = $this->tikTokService->publish(
                socialAccount: $socialAccount,
                file: $localPath,
                areCommentsOff: $message->areCommentsOff,
                isDuetOff: $message->isDuetOff,
                isStitchOff: $message->isStitchOff,
            );

            if (!$publishInfoTikTok->isSuccess()) {
                throw new UploadTikTokClipException(username: $socialAccount->getUsername(), errorCode: $publishInfoTikTok->getErrorCode(), message: $publishInfoTikTok->getErrorMessage());
            }

            $videoPublish = $this->videoPublishRepository->createOrUpdate([
                'video' => $video,
                'socialAccount' => $socialAccount,
            ], [
                'video' => $video,
                'socialAccount' => $socialAccount,
                'publishId' => $publishInfoTikTok->getPublishId(),
                'message' => null,
            ]);

            $video->addVideoPublish($videoPublish);
            $this->videoRepository->save($video);

            $this->messageBus->dispatch(new UploadTikTokVideoStatus(
                videoId: $video->getId(),
                clipId: $clip->getId(),
                socialAccountId: $socialAccount->getId(),
                checkId: uniqid(),
            ), [new AmqpStamp('async'), new DelayStamp(10000)]);

            return;
        } catch (\Exception $exception) {
            $this->messageBus->dispatch(new UpdateVideoPublishStatus(
                videoId: $video->getId(),
                clipId: $clip->getId(),
                status: VideoPublishStatus::name(VideoPublishStatus::ERROR),
                message: $exception->getMessage(),
                socialAccountId: $message->socialAccountId,
            ));
        }
    }
}
