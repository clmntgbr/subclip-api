<?php

namespace App\Repository;

use App\Entity\ApiKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiKey>
 */
class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    public function remove(ApiKey $apiKey): void
    {
        $this->getEntityManager()->remove($apiKey);
        $this->getEntityManager()->flush();
    }

    public function save(ApiKey $apiKey): void
    {
        $this->getEntityManager()->persist($apiKey);
        $this->getEntityManager()->flush();
    }
}
