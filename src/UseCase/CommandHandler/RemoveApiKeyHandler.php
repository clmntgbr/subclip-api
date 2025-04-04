<?php

namespace App\UseCase\CommandHandler;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\UseCase\Command\RemoveApiKey;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RemoveApiKeyHandler
{
    public function __construct(
        private ApiKeyRepository $apiKeyRepository,
    ) {
    }

    public function __invoke(RemoveApiKey $message): void
    {
        /** @var ?ApiKey $apiKey */
        $apiKey = $this->apiKeyRepository->findOneBy(['id' => $message->apiKey]);

        if (null === $apiKey) {
            return;
        }

        $apiKey->eraseToken();
        $this->apiKeyRepository->save($apiKey);
    }
}
