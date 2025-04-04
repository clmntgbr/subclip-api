<?php

namespace App\UseCase\CommandHandler;

use App\Entity\Clip;
use App\Entity\Configuration;
use App\Entity\Video;
use App\Message\TaskMessage;
use App\Repository\ClipRepository;
use App\Repository\UserRepository;
use App\Repository\VideoRepository;
use App\UseCase\Command\CreateClip;
use App\UseCase\Command\CreateVideo;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
final class CreateClipHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private ClipRepository $clipRepository,
        private VideoRepository $videoRepository,
        private MessageBusInterface $messageBus,
        private WorkflowInterface $clipStateMachine,
    ) {
    }

    public function __invoke(CreateClip $message): void
    {
        $user = $this->userRepository->findOneBy(['id' => $message->userId->__toString()]);

        if (null === $user) {
            throw new \InvalidArgumentException(sprintf('User does not exist with id %s', $message->userId->__toString()));
        }

        $videoId = Uuid::v4();

        $this->messageBus->dispatch(new CreateVideo(
            $videoId,
            $message->originalName,
            $message->name,
            $message->mimeType,
            $message->size,
        ));

        /** @var ?Video $video */
        $video = $this->videoRepository->findOneBy(['id' => $videoId->__toString()]);

        if (null === $video) {
            throw new \InvalidArgumentException(sprintf('Video does not exist with id %s', $$videoId->__toString()));
        }

        $clip = new Clip($user, $message->clipId, $video, new Configuration());

        if (!$this->clipStateMachine->can($clip, 'process_sound_extractor')) {
            throw new \RuntimeException('Clip is not in a valid state to process sound');
        }

        $this->clipStateMachine->apply($clip, 'process_sound_extractor');
        $this->clipRepository->save($clip);

        $this->messageBus->dispatch(new TaskMessage($clip->getId(), 'sound_extractor'));

        return;
    }
}
