<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\VideoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
#[ApiResource]
class Video
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ApiProperty(identifier: true)]
    #[Groups([CLIP_READ])]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([CLIP_READ])]
    private ?string $originalName = null;

    #[ORM\Column(type: Types::STRING)]
    #[Groups([CLIP_READ])]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING)]
    #[Groups([CLIP_READ])]
    private ?string $mimeType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups([CLIP_READ])]
    private ?int $size = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups([CLIP_READ])]
    private ?int $length = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([CLIP_READ])]
    private ?string $subtitle = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([CLIP_READ])]
    private ?string $ass = null;

    #[ORM\Column(type: Types::JSON, nullable: false)]
    #[Groups([CLIP_READ])]
    private array $subtitles = [];

    #[ORM\Column(type: Types::JSON, nullable: false)]
    #[Groups([CLIP_READ])]
    private array $audios = [];

    #[ManyToOne(targetEntity: VideoPublish::class, cascade: ['persist', 'remove'])]
    #[JoinColumn(name: 'video_publish_id', referencedColumnName: 'id', nullable: true)]
    private ?VideoPublish $videoPublish = null;

    public function __construct(
        Uuid $videoId,
        string $originalName,
        string $name,
        string $mimeType,
        int $size,
    ) {
        $this->id = $videoId;
        $this->originalName = $originalName;
        $this->name = $name;
        $this->mimeType = $mimeType;
        $this->size = $size;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function getAudios(): array
    {
        return $this->audios;
    }

    public function getSubtitles(): array
    {
        return $this->subtitles;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function getAss(): ?string
    {
        return $this->ass;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setLength(int $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function setSubtitle(string $subtitle): self
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function setAss(string $ass): self
    {
        $this->ass = $ass;

        return $this;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function setSubtitles(array $subtitles): self
    {
        $this->subtitles = $subtitles;

        return $this;
    }

    public function setAudios(array $audios): self
    {
        $this->audios = $audios;

        return $this;
    }

    public function addSubtitles(string $subtitle): static
    {
        $this->subtitles[] = $subtitle;

        return $this;
    }

    public function addAudios(string $audio): static
    {
        $this->audios[] = $audio;

        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = Uuid::fromString($id);

        return $this;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function setVideoPublish(VideoPublish $videoPublish): self
    {
        $this->videoPublish = $videoPublish;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getVideoPublish(): ?VideoPublish
    {
        return $this->videoPublish;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
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
