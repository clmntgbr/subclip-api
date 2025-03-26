<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\ApiResource\UploadVideoAction;
use App\Entity\ValueObject\Status;
use App\Repository\ClipRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ClipRepository::class)]
#[ApiResource(
    order: ['createdAt' => 'DESC'],
    operations: [
        new Post(
            uriTemplate: '/video/upload',
            controller: UploadVideoAction::class,
        ),
    ]
)]
class Clip
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ApiProperty(identifier: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Embedded(class: Status::class, columnPrefix: false)]
    private Status $status;

    public function __construct(
        User $user, 
        Uuid $id
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->status = new Status('uploaded');
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
