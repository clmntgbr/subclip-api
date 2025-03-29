<?php

namespace App\MessageHandler;

use App\Entity\Clip;
use App\Entity\Video;
use App\Message\CreateClip;
use App\Message\CreateVideo;
use App\Message\MicroServicesMessage;
use App\Repository\ClipRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsMessageHandler]
final class CreateClipHandler
{
    public function __construct(
        private ClipRepository $clipRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(CreateClip $message): Clip
    {
        $envelope = $this->messageBus->dispatch(new CreateVideo(
            $message->originalName,
            $message->name,
            $message->mimeType,
            $message->size,
        ));

        $handledStamp = $envelope->last(HandledStamp::class);

        /** @var Video $video */
        $video = $handledStamp->getResult();

        $clip = new Clip($message->user, $message->clipId, $video);
        $this->clipRepository->save($clip);

        $this->messageBus->dispatch(new MicroServicesMessage($clip, 'sound_extractor'));

        return $clip;
    }
}
