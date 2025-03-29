<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\ApiResource\UploadVideoAction;
use App\Protobuf\ClipStatus;
use App\Repository\ClipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Groups;
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

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Groups([CLIP_READ])]
    private string $status;

    #[ORM\Column(type: Types::JSON, nullable: false)]
    #[Groups([CLIP_READ])]
    private array $statuses = [];

    public function __construct(
        User $user,
        Uuid $clipId,
        Video $originalVideo,
    ) {
        $this->id = $clipId;
        $this->user = $user;
        $this->originalVideo = $originalVideo;
        $this->status = ClipStatus::name(ClipStatus::UPLOADED);
        $this->statuses = [ClipStatus::name(ClipStatus::UPLOADED)];
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->statuses[] = $status;

        return $this;
    }

    public function setCover(string $cover): self
    {
        $this->cover = $cover;

        return $this;
    }

    public function setOriginalVideo(Video $video): self
    {
        $this->originalVideo = $video;

        return $this;
    }

    #[Groups([CLIP_READ])]
    public function getOriginalVideo(): Video
    {
        return $this->originalVideo;
    }

    #[Groups([CLIP_READ])]
    public function getCover(): ?string
    {
        return $this->cover;
    }

    #[Groups([CLIP_READ])]
    public function getProcessedVideo(): ?Video
    {
        return $this->processedVideo;
    }

    public function getStatuses(): array
    {
        return $this->statuses;
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
