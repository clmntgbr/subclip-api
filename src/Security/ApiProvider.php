<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
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
