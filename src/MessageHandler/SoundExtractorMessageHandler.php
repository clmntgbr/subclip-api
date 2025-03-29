<?php

namespace App\MessageHandler;

use App\Message\MicroServicesMessage;
use App\Message\SoundExtractorMessage;
use App\Protobuf\ClipStatus;
use App\Repository\ClipRepository;
use App\Service\ProtobufService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
final class SoundExtractorMessageHandler
{
    public function __construct(
        private readonly ProtobufService $protobufService,
        private WorkflowInterface $clipStateMachine,
        private ClipRepository $clipRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(SoundExtractorMessage $message): void
    {
        $clip = $this->protobufService->getClip($message->apiMessage->getClip());

        $canTransition = false;
        foreach ($this->clipStateMachine->getEnabledTransitions($clip) as $transition) {
            if (in_array($message->apiMessage->getClip()->getStatus(), $transition->getTos())) {
                $canTransition = true;
                break;
            }
        }

        if (!$canTransition) {
            $clip->setStatus(ClipStatus::name(ClipStatus::STATUS_ERROR));
            $this->clipRepository->save($clip);

            return;
        }

        foreach ($this->clipStateMachine->getEnabledTransitions($clip) as $transition) {
            if (in_array($message->apiMessage->getClip()->getStatus(), $transition->getTos())) {
                $this->clipStateMachine->apply($clip, $transition->getName());
                $this->clipRepository->save($clip);
                break;
            }
        }

        if (!$this->clipStateMachine->can($clip, 'process_subtitles')) {
            return;
        }

        $this->clipStateMachine->apply($clip, 'process_subtitles');
        $this->clipRepository->save($clip);

        $this->messageBus->dispatch(new MicroServicesMessage($clip, 'subtitle_generator'));

        return;
    }
}
