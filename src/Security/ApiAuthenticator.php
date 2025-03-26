<?php

namespace App\Security;

use App\Entity\ApiKey;
use App\Message\RemoveApiKey;
use App\Repository\ApiKeyRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ApiKeyRepository $apiKeyRepository,
        private UserRepository $userRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-API-KEY');
    }

    public function authenticate(Request $request): Passport
    {
        $plainToken = $request->headers->get('X-API-KEY');
        $hashedToken = hash('sha256', $plainToken);

        /** @var ?ApiKey $apiKey */
        $apiKey = $this->apiKeyRepository->findOneBy(['token' => $hashedToken]);

        if (null === $apiKey) {
            throw new AuthenticationException();
        }

       if ($apiKey->getExpireAt() < new \DateTimeImmutable()) {
            $this->messageBus->dispatch(new RemoveApiKey($apiKey->getId()));
            throw new AuthenticationException();
        }

        $user = $apiKey->getUser();

        if (null === $user) {
            throw new AuthenticationException();
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getId()->__toString())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}