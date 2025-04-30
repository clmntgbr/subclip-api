<?php

namespace App\MessageHandler;

use App\Message\TaskMessage;
use App\Protobuf\TaskMessage as ProtobufTaskMessage;
use App\Repository\ClipRepository;
use App\Service\ProtobufService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class TaskMessageHandler
{
    public function __construct(
        private ProtobufService $protobufService,
        private MessageBusInterface $messageBus,
        private ClipRepository $clipRepository,
    ) {
    }

    public function __invoke(TaskMessage $message): void
    {
        $clip = $this->clipRepository->findOneBy(['id' => $message->clipId->toString()]);

        if (null === $clip) {
            return;
        }

        $protobufClip = $this->protobufService->getProtobufClip($clip);

        $protobufTaskMessage = new ProtobufTaskMessage();
        $protobufTaskMessage->setClip($protobufClip);
        $protobufTaskMessage->setService($message->service);

        $this->messageBus->dispatch($protobufTaskMessage, [
            new AmqpStamp($message->service),
        ]);
    }
}
