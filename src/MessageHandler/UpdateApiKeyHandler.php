<?php

namespace App\MessageHandler;

use App\Entity\ApiKey;
use App\Message\UpdateApiKey;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateApiKeyHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(UpdateApiKey $message): void
    {
        $user = $message->user;
        $token = $message->token;

        if (null === $user->getApiKey()) {
            $apiKey = new ApiKey();
            $apiKey->setUser($user);
            $user->setApiKey($apiKey);
        }

        $user->getApiKey()->setToken($token);
        $user->getApiKey()->setExpireAt(new \DateTimeImmutable('+7 days'));

        $this->userRepository->save($user);
    }
}
