<?php

namespace App\Security;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Repository\UserRepository;
use App\Service\TokenManager;
use App\UseCase\Command\RemoveApiKey;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ApiKeyRepository $apiKeyRepository,
        private UserRepository $userRepository,
        private MessageBusInterface $messageBus,
        private TokenManager $tokenManager,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
               && str_starts_with($request->headers->get('Authorization'), 'Bearer');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $this->extractToken($request);

        if (null === $token) {
            throw new AuthenticationException();
        }

        $userId = $this->tokenManager->validateToken($token);
        dd($userId);

        return $passport;

        dd($payload, $idClaim);
        $plainToken = $request->headers->get('X-API-KEY');
        $hashedToken = hash('sha256', $plainToken);

        /** @var ?ApiKey $apiKey */
        $apiKey = $this->apiKeyRepository->findOneBy(['token.value' => $hashedToken]);

        if (null === $apiKey) {
            throw new AuthenticationException();
        }

        if ($apiKey->getExpireAt() < new \DateTimeImmutable()) {
            $this->messageBus->dispatch(new RemoveApiKey(apiKey: $apiKey->getId()));
            throw new AuthenticationException();
        }

        $user = $apiKey->getUser();

        if (null === $user) {
            throw new AuthenticationException();
        }

        return new SelfValidatingPassport(
            userBadge: new UserBadge(userIdentifier: $user->getId()->__toString())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    private function extractToken(Request $request): ?string
    {
        $authorizationHeader = $request->headers->get('Authorization');

        if (!$authorizationHeader || !preg_match('/Bearer\s+(.*)$/i', $authorizationHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
