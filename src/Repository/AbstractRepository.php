<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class AbstractRepository extends ServiceEntityRepository
{
    public function delete(object $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

    public function save(object $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function refresh(object $entity): object
    {
        $this->getEntityManager()->refresh($entity);
        return $entity;
    }
}
