<?php

namespace App\MessageHandler;

use App\Entity\Clip;
use App\Message\CreateClip;
use App\Repository\ClipRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateClipHandler
{
    public function __construct(
        private ClipRepository $clipRepository,
    )
    {
    }
    public function __invoke(CreateClip $message): void
    {
        $clip = new Clip($message->user, $message->clipId);
        $this->clipRepository->save($clip);
    }
}
