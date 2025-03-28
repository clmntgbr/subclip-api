<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\ApiResource\UploadVideoAction;
use App\Entity\ValueObject\Status;
use App\Entity\ValueObject\Statuses;
use App\Protobuf\ClipStatus;
use App\Repository\ClipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

const CLIP_READ = 'clip.read';
const CLIP_WRITE = 'clip.write';

#[ORM\Entity(repositoryClass: ClipRepository::class)]
#[ApiResource(
    order: ['createdAt' => 'DESC'],
    operations: [
        new Post(
            uriTemplate: '/video/upload',
            controller: UploadVideoAction::class,
        ),
        new GetCollection(
            normalizationContext: ['skip_null_values' => false, 'groups' => [CLIP_READ]],
        ),
        new Get(
            normalizationContext: ['skip_null_values' => false, 'groups' => [CLIP_READ]],
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

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cover = null;

    #[ORM\OneToOne(targetEntity: Video::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'orginal_video_id', referencedColumnName: 'id', nullable: false)]
    private ?Video $originalVideo = null;

    #[ORM\OneToOne(targetEntity: Video::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'processed_video_id', referencedColumnName: 'id', nullable: true)]
    private ?Video $processedVideo = null;

    #[Embedded(class: Status::class, columnPrefix: false)]
    private Status $status;

    #[Embedded(class: Statuses::class, columnPrefix: false)]
    private Statuses $statuses;

    public function __construct(
        User $user,
        Uuid $clipId,
        Video $originalVideo,
    ) {
        $this->id = $clipId;
        $this->user = $user;
        $this->originalVideo = $originalVideo;
        $this->status = new Status(ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_PENDING));
        $this->statuses = new Statuses([ClipStatus::name(ClipStatus::UPLOADED), ClipStatus::name(ClipStatus::SOUND_EXTRACTOR_PENDING)]);
    }

    #[Groups([CLIP_READ])]
    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    #[Groups([CLIP_READ])]
    public function getOriginalVideo(): Video
    {
        return $this->originalVideo;
    }

    #[Groups([CLIP_READ])]
    public function getCover(): string
    {
        return $this->cover;
    }

    #[Groups([CLIP_READ])]
    public function getProcessedVideo(): ?Video
    {
        return $this->processedVideo;
    }

    #[Groups([CLIP_READ])]
    #[SerializedName('status')]
    public function getStatusToString(): string
    {
        return $this->status->__toString();
    }

    #[Groups([CLIP_READ])]
    #[SerializedName('statuses')]
    public function getApiStatuses(): array
    {
        return $this->statuses->getValues();
    }

    #[Groups([CLIP_READ])]
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[Groups([CLIP_READ])]
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
