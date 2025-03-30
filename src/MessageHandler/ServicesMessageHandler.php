<?php

namespace App\MessageHandler;

use App\Message\ServicesMessage;
use App\Protobuf\ServicesMessage as ProtobufServicesMessage;
use App\Service\ProtobufService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class ServicesMessageHandler
{
    public function __construct(
        private ProtobufService $protobufService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(ServicesMessage $message): void
    {
        $protobufClip = $this->protobufService->getProtobufClip($message->clip);

        $protobufServicesMessage = new ProtobufServicesMessage();
        $protobufServicesMessage->setClip($protobufClip);
        $protobufServicesMessage->setService($message->service);

        $this->messageBus->dispatch($protobufServicesMessage, [
            new AmqpStamp($message->service, 0, []),
        ]);
    }
}
