<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\VideoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
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

    public function __construct(
        string $originalName,
        string $name,
        string $mimeType,
        int $size,
    ) {
        $this->id = Uuid::v4();
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

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function getMimeType()
    {
        return $this->mimeType;
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
