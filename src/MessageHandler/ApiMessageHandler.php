<?php

namespace App\MessageHandler;

use App\Protobuf\ApiMessage;
use App\Service\ProtobufService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class ApiMessageHandler
{
    public function __construct(
        private ProtobufService $protobufService,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(ApiMessage $message): void
    {
        dd($message->getClip());
    }
}
