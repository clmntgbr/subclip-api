<?php

namespace App\Security;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Repository\ApiKeyRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    /** @param User $user */
    public function refreshUser(UserInterface $user): UserInterface
    {
        $user = $this->userRepository->findOneBy(['id' => $user->getId()]);

        if (null === $user) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneBy(['id' => $identifier]);

        if (null === $user) {
            $e = new UserNotFoundException(sprintf('Api key "%s" not found.', $identifier));
            $e->setUserIdentifier($identifier);
            throw $e;
        }

        return $user;
    }
}