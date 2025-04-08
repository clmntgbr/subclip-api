<?php

namespace App\UseCase\CommandHandler;

use App\Entity\Clip;
use App\Entity\SocialAccount;
use App\Exception\UploadTikTokClipException;
use App\Model\TikTok\TokenTikTok;
use App\Repository\ClipRepository;
use App\Repository\SocialAccountRepository;
use App\Service\TikTokService;
use App\UseCase\Command\UpdateTikTokToken;
use App\UseCase\Command\UploadTikTokClip;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class UploadTikTokClipHandler
{
    public function __construct(
        private ClipRepository $clipRepository,
        private SocialAccountRepository $socialAccountRepository,
        private TikTokService $tikTokService,
        private MessageBusInterface $messageBus,
        private FilesystemOperator $awsStorage,
    ) {
    }

    public function __invoke(UploadTikTokClip $message): void
    {
        /** @var ?Clip $clip */
        $clip = $this->clipRepository->findOneBy(['id' => $message->clipId->__toString()]);

        if (null === $clip) {
            throw new UploadTikTokClipException(sprintf('Clip does not exist with id %s', $message->clipId->__toString()));
        }

        /** @var ?SocialAccount $socialAccount */
        $socialAccount = $this->socialAccountRepository->findOneBy(['id' => $message->socialAccountId->__toString()]);

        if (null === $socialAccount) {
            throw new UploadTikTokClipException(sprintf('Social Account does not exist with id %s', $message->clipId->__toString()));
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

        if (!$creatorQuery->hasPrivacyOption(TikTokService::PRIVACY_PUBLIC)) {
			throw new UploadTikTokClipException('TikTok Error: This Creator cannot publish with the privacy level '.implode(', ', $creatorQuery->getPrivacyOptions()));
		}

		if ($creatorQuery->areCommentsOff() && !false) {
			throw new UploadTikTokClipException('TikTok Error: This Creator cannot publish without turning off the Comments');
		}

		if ($creatorQuery->isDuetOff() && !false) {
			throw new UploadTikTokClipException('TikTok Error: This Creator cannot publish without turning off Duet');
		}

		if ($creatorQuery->isStitchOff() && !false) {
			throw new UploadTikTokClipException('TikTok Error: This Creator cannot publish without turning off Stitch');
		}

        $path = sprintf('%s/%s/%s', $clip->getUser()->getId(), $clip->getId()->toString(), $clip->getOriginalVideo()->getName());
        $localPath = sprintf('var/tmp/%s', basename($path));
        
        $dirPath = dirname($localPath);
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        $stream = $this->awsStorage->readStream($path);

        if (!$stream) {
            throw new UploadTikTokClipException('Failed to open stream for reading');
        }

        $localFile = fopen($localPath, 'w');
        stream_copy_to_stream($stream, $localFile);
        fclose($localFile);
        fclose($stream);
        
        dd($creatorQuery);

        return;
    }
}
