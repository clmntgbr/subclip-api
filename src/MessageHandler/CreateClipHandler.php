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
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
final class CreateClipHandler
{
    public function __construct(
        private ClipRepository $clipRepository,
        private MessageBusInterface $messageBus,
        private WorkflowInterface $clipStateMachine,
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

        if (!$this->clipStateMachine->can($clip, 'process_sound')) {
            throw new \RuntimeException('Clip is not in a valid state to process sound');
        }

        $this->clipStateMachine->apply($clip, 'process_sound');
        $this->clipRepository->save($clip);

        $this->messageBus->dispatch(new MicroServicesMessage($clip, 'sound_extractor'));

        return $clip;
    }
}
