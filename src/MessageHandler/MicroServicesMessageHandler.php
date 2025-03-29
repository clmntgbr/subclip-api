<?php

namespace App\MessageHandler;

use App\Message\MicroServicesMessage;
use App\Protobuf\MicroServicesMessage as ProtobufMicroServicesMessage;
use App\Service\ProtobufService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class MicroServicesMessageHandler
{
    public function __construct(
        private ProtobufService $protobufService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(MicroServicesMessage $message): void
    {
        $protobufClip = $this->protobufService->getProtobufClip($message->clip);

        $protobufMicroServicesMessage = new ProtobufMicroServicesMessage();
        $protobufMicroServicesMessage->setClip($protobufClip);
        $protobufMicroServicesMessage->setService($message->service);

        $this->messageBus->dispatch($protobufMicroServicesMessage, [
            new AmqpStamp($message->service, 0, []),
        ]);
    }
}
