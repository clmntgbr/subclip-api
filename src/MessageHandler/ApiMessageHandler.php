<?php

namespace App\MessageHandler;

use App\Message\SoundExtractorMessage;
use App\Protobuf\ApiMessage;
use App\Protobuf\ClipStatus;
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
        match ($message->getClip()->getStatus()) {
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_PENDING) => $this->messageBus->dispatch(new SoundExtractorMessage($message)),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_COMPLETE) => $this->messageBus->dispatch(new SoundExtractorMessage($message)),
            ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_ERROR) => $this->messageBus->dispatch(new SoundExtractorMessage($message)),

            default => throw new \RuntimeException('Unknown message type'),
        };
    }
}
