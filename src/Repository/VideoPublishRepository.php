<?php

namespace App\Repository;

use App\Entity\VideoPublish;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VideoPublish>
 */
class VideoPublishRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VideoPublish::class);
    }

    public function createOrUpdate(array $search, array $payload): VideoPublish
    {
        $videoPublish = $this->findOneBy($search);

        if (null === $videoPublish) {
            $videoPublish = new VideoPublish(
                video: $payload['video'] ?? null,
                socialAccount: $payload['socialAccount'] ?? null,
                publishId: $payload['publishId'] ?? null,
            );
        }

        return $this->update($videoPublish, $payload);
    }
}
