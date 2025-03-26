<?php

namespace App\Repository;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Entity\ValueObject\Password;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends AbstractRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword(new Password($newHashedPassword));
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function updateToken(User $user, string $token): User
    {
        if (null === $user->getApiKey()) {
            $apiKey = new ApiKey();
            $apiKey->setUser($user);
            $user->setApiKey($apiKey);
        }

        $user->getApiKey()->setToken($token);
        $user->getApiKey()->setExpireAt(new \DateTimeImmutable('+7 days'));

        $this->save($user);

        return $user;
    }
}
