<?php

namespace App\UseCase\CommandHandler;

use App\Entity\Clip;
use App\Entity\SocialAccount;
use App\Exception\UploadTikTokClipException;
use App\Protobuf\ClipStatus;
use App\Repository\ClipRepository;
use App\Repository\SocialAccountRepository;
use App\Service\FileService;
use App\Service\TikTokService;
use App\UseCase\Command\UpdateClipStatus;
use App\UseCase\Command\UpdateTikTokToken;
use App\UseCase\Command\UploadTikTokClip;
use App\UseCase\Command\UploadTikTokVideo;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class UploadTikTokClipHandler
{
    public function __construct(
        private ClipRepository $clipRepository,
        private SocialAccountRepository $socialAccountRepository,
        private TikTokService $tikTokService,
        private MessageBusInterface $messageBus,
        private FileService $fileService,
    ) {
    }

    public function __invoke(UploadTikTokClip $message): void
    {
        try {
            /** @var ?Clip $clip */
            $clip = $this->clipRepository->findOneBy(['id' => $message->clipId->__toString()]);

            if (null === $clip) {
                throw new UploadTikTokClipException(sprintf('Clip does not exist with id [%s]', $message->clipId->__toString()));
            }

            /** @var ?SocialAccount $socialAccount */
            $socialAccount = $this->socialAccountRepository->findOneBy(['id' => $message->socialAccountId->__toString()]);

            if (null === $socialAccount) {
                throw new UploadTikTokClipException(sprintf('Social Account does not exist with id [%s]', $message->socialAccountId->__toString()));
            }

            if ($socialAccount->getExpireAt() < new \DateTimeImmutable() && $socialAccount->getRefreshExpireAt() < new \DateTimeImmutable()) {
                throw new UploadTikTokClipException('Tokens are expired');
            }

            if ($socialAccount->getExpireAt() < new \DateTimeImmutable()) {
                $this->messageBus->dispatch(new UpdateTikTokToken($socialAccount->getId()));
                /** @var SocialAccount $socialAccount */
                $socialAccount = $this->socialAccountRepository->refresh($socialAccount);
            }

            $creatorQuery = $this->tikTokService->getCreatorInfo($socialAccount->getAccessToken());

            if (!$creatorQuery->hasPrivacyOption(TikTokService::PRIVACY_PRIVATE)) {
                throw new UploadTikTokClipException('TikTok Error: This Creator cannot publish with the privacy level '.implode(', ', $creatorQuery->getPrivacyOptions()));
            }

            $this->messageBus->dispatch(new UpdateClipStatus(
                clipId: $clip->getId(),
                status: ClipStatus::name(ClipStatus::CLIP_UPLOADING),
            ));

            $this->messageBus->dispatch(new UploadTikTokVideo(
                clipId: $clip->getId(),
                videoId: $clip->getProcessedVideo()->getId(),
                socialAccountId: $socialAccount->getId(),
                maxDuration: $creatorQuery->getMaxVideoDuration(),
                areCommentsOff: $creatorQuery->areCommentsOff(),
                isDuetOff: $creatorQuery->isDuetOff(),
                isStitchOff: $creatorQuery->isStitchOff(),
            ), [new AmqpStamp('async', 0, [])]);
        } catch (\Exception $exception) {
            $this->messageBus->dispatch(new UpdateClipStatus(
                clipId: $clip->getId(),
                status: ClipStatus::name(ClipStatus::STATUS_ERROR),
                message: $exception->getMessage(),
            ));
        }
    }
}
