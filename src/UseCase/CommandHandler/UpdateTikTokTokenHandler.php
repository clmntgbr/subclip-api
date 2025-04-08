<?php

namespace App\UseCase\CommandHandler;

use App\Entity\ApiKey;
use App\Repository\SocialAccountRepository;
use App\Repository\UserRepository;
use App\Service\TikTokService;
use App\UseCase\Command\UpdateApiKey;
use App\UseCase\Command\UpdateTikTokToken;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateTikTokTokenHandler
{
    public function __construct(
        private SocialAccountRepository $socialAccountRepository,
        private TikTokService $tikTokService,
    ) {
    }

    public function __invoke(UpdateTikTokToken $message): void
    {
        $socialAccount = $this->socialAccountRepository->findOneBy(['id' => $message->socialAccountId->__toString()]);

        if (null === $socialAccount) {
            return;
        }

        $tokenTikTok = $this->tikTokService->refreshToken($socialAccount->getRefreshToken());

        $now = new \DateTime('now');

        $expireAt = clone $now;
        $expireAt->modify(sprintf('+%s seconds', $tokenTikTok->getExpiresIn()));

        $refreshExpireAt = clone $now;
        $refreshExpireAt->modify(sprintf('+%s seconds', $tokenTikTok->getRefreshExpiresIn()));

        $socialAccount->updateToken(
            accessToken: $tokenTikTok->getAccessToken(), 
            refreshToken: $tokenTikTok->getRefreshToken(), 
            expireAt: $expireAt, 
            refreshExpireAt: $refreshExpireAt,
        );

        $this->socialAccountRepository->save($socialAccount);
    }
}
