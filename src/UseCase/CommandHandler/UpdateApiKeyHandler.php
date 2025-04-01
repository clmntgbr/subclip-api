<?php

namespace App\UseCase\CommandHandler;

use App\Entity\ApiKey;
use App\UseCase\Command\UpdateApiKey;
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
        $user = $this->userRepository->findOneBy(['id' => $message->userId->__toString()]);

        if ($user === null) {
            return;
        }

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
