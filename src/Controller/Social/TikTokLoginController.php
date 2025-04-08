<?php

namespace App\Controller\Social;

use App\Entity\SocialAccount;
use App\Entity\User;
use App\Protobuf\SocialAccountType;
use App\Repository\SocialAccountRepository;
use App\Repository\UserRepository;
use App\Service\TikTokService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

#[Route('/api/tiktok', name: 'api_tiktok_')]
class TikTokLoginController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private SocialAccountRepository $socialAccountRepository,
        private TikTokService $tikTokService,
    ) {
    }

    #[Route('/login', name: 'login', methods: ['GET'])]
    public function login(#[CurrentUser()] ?User $user): JsonResponse
    {
        $state = Uuid::v4()->toRfc4122();

        $user->setState($state);
        $this->userRepository->save($user);

        return new JsonResponse(data: [
            'login_url' => $this->tikTokService->getLoginUrl($state),
        ], status: Response::HTTP_OK);
    }

    #[Route('/callback', name: 'callback', methods: ['GET'])]
    public function callback(Request $request): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['state' => $request->query->get('state')]);

        if (null === $user) {
            return new JsonResponse(data: ['message' => 'User not found with this state. Login again.'], status: Response::HTTP_BAD_REQUEST);
        }

        $tokenTikTok = $this->tikTokService->getToken($request->query->get('code'));
        $userTikTok = $this->tikTokService->getUserInfo($tokenTikTok->getAccessToken());

        $now = new \DateTime('now');

        $expireAt = clone $now;
        $expireAt->modify(sprintf('+%s seconds', $tokenTikTok->getExpiresIn()));

        $refreshExpireAt = clone $now;
        $refreshExpireAt->modify(sprintf('+%s seconds', $tokenTikTok->getRefreshExpiresIn()));

        /** @var SocialAccount $socialAccount */
        $socialAccount = $this->socialAccountRepository->findOneBy([
            'user' => $user,
            'socialAccountId.value' => $userTikTok->getOpenID(),
        ]);

        if (null === $socialAccount) {
            $socialAccount = new SocialAccount(
                user: $user,
                type: SocialAccountType::name(SocialAccountType::TIKTOK),
                socialAccountId: $userTikTok->getOpenID(),
            );
        }

        $socialAccount->update(
            username: $userTikTok->getDisplayName(),
            accessToken: $tokenTikTok->getAccessToken(),
            scope: $tokenTikTok->getScope(),
            refreshToken: $tokenTikTok->getRefreshToken(),
            expireAt: $expireAt,
            refreshExpireAt: $refreshExpireAt,
        );

        $this->socialAccountRepository->save($socialAccount);

        return new JsonResponse(data: ['message' => 'TikTok account linked'], status: Response::HTTP_OK);
    }
}
