<?php

namespace App\UseCase\CommandHandler;

use App\Entity\Clip;
use App\Entity\SocialAccount;
use App\Entity\Video;
use App\Entity\VideoPublish;
use App\Exception\UploadTikTokClipException;
use App\Protobuf\VideoPublishStatus;
use App\Repository\ClipRepository;
use App\Repository\SocialAccountRepository;
use App\Repository\VideoRepository;
use App\Service\FileService;
use App\Service\TikTokService;
use App\UseCase\Command\UpdateVideoStatus;
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
            $clip = $this->clipRepository->findOneBy(['id' => $message->clipId->__toString()]);

            if (null === $clip) {
                throw new UploadTikTokClipException(sprintf('Clip does not exist with id [%s]', $message->clipId->__toString()));
            }

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

            if ($video->getLength() > $message->maxDuration) {
                throw new UploadTikTokClipException('TikTok Error: The video is too long. Max length is '.$video->getLength().' seconds');
            }

            $localPath = $this->fileService->downloadFromS3(
                userId: $clip->getUser()->getId(),
                clipId: $clip->getId(),
                fileName: $clip->getProcessedVideo()->getName()
            );

            $publishInfoTikTok = $this->tikTokService->publish(
                accessToken: $socialAccount->getAccessToken(),
                file: $localPath,
                areCommentsOff: $message->areCommentsOff,
                isDuetOff: $message->isDuetOff,
                isStitchOff: $message->isStitchOff,
            );

            if (!$publishInfoTikTok->isSuccess()) {
                throw new UploadTikTokClipException($publishInfoTikTok->getErrorMessage());
            }

            $video->setVideoPublish(new VideoPublish($publishInfoTikTok->getPublishId()));
            $this->videoRepository->save($video);

            $this->messageBus->dispatch(new UploadTikTokVideoStatus(
                videoId: $video->getId(),
                socialAccountId: $socialAccount->getId(),
                checkId: uniqid(),
            ), [new AmqpStamp('async'), new DelayStamp(10000)]);

            return;
        } catch (\Exception $exception) {
            $this->messageBus->dispatch(new UpdateVideoStatus(
                videoId: $video->getId(),
                status: VideoPublishStatus::name(VideoPublishStatus::ERROR),
                message: $exception->getMessage(),
            ));
        }
    }
}
