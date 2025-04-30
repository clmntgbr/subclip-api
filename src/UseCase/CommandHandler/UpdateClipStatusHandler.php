<?php

namespace App\UseCase\CommandHandler;

use App\Entity\Clip;
use App\Protobuf\ClipStatus;
use App\Repository\ClipRepository;
use App\UseCase\Command\UpdateClipStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateClipStatusHandler
{
    public function __construct(
        private ClipRepository $clipRepository,
    ) {
    }

    public function __invoke(UpdateClipStatus $message): void
    {
        /** @var ?Clip $clip */
        $clip = $this->clipRepository->findOneBy(['id' => $message->clipId->toString()]);

        if (null === $clip) {
            throw new \Exception(sprintf('Clip does not exist with id [%s]', $message->clipId->toString()));
        }

        if ($message->status === ClipStatus::name(ClipStatus::CLIP_UPLOADING)) {
            $clip->updateStatusUploading();
        }

        if ($message->status === ClipStatus::name(ClipStatus::STATUS_ERROR)) {
            $clip->updateStatusError($message->message);
        }

        $this->clipRepository->save($clip);

        return;
    }
}
