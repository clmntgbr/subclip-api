<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Protobuf\ConfigurationSubtitleFont;
use App\Protobuf\ConfigurationSubtitleOutlineThickness;
use App\Protobuf\ConfigurationSubtitleShadow;
use App\Protobuf\VideoFormatStyle;
use App\Repository\ConfigurationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ConfigurationRepository::class)]
#[ApiResource]
class Configuration
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ApiProperty(identifier: true)]
    #[Groups([CLIP_READ])]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Groups([CLIP_READ])]
    private string $subtitleFont;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Groups([CLIP_READ])]
    private int $subtitleSize;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Groups([CLIP_READ])]
    private string $subtitleColor;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Groups([CLIP_READ])]
    private string $subtitleBold;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Groups([CLIP_READ])]
    private string $subtitleItalic;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Groups([CLIP_READ])]
    private string $subtitleUnderline;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Groups([CLIP_READ])]
    private string $subtitleOutlineColor;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Groups([CLIP_READ])]
    private int $subtitleOutlineThickness;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[Groups([CLIP_READ])]
    private int $subtitleShadow;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    #[Groups([CLIP_READ])]
    private string $subtitleShadowColor;

    #[ORM\Column(type: Types::STRING)]
    #[Groups([CLIP_READ])]
    private string $format;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups([CLIP_READ])]
    private int $split;

    #[ORM\Column(type: Types::FLOAT)]
    #[Groups([CLIP_READ])]
    private float $marginV;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->subtitleFont = ConfigurationSubtitleFont::name(ConfigurationSubtitleFont::ARIAL);
        $this->subtitleSize = 16;
        $this->subtitleColor = '#FFFFFF';
        $this->subtitleBold = '0';
        $this->subtitleItalic = '0';
        $this->subtitleUnderline = '0';
        $this->subtitleOutlineColor = '#000000';
        $this->subtitleOutlineThickness = ConfigurationSubtitleOutlineThickness::OUTLINE_MEDIUM;
        $this->subtitleShadow = ConfigurationSubtitleShadow::SHADOW_MEDIUM;
        $this->subtitleShadowColor = '#000000';
        $this->format = VideoFormatStyle::name(VideoFormatStyle::ORIGINAL);
        $this->split = 1;
        $this->marginV = 0;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getSubtitleFont(): string
    {
        return $this->subtitleFont;
    }

    public function getSubtitleSize(): int
    {
        return $this->subtitleSize;
    }

    public function getSubtitleColor(): string
    {
        return $this->subtitleColor;
    }

    public function getSubtitleBold(): string
    {
        return $this->subtitleBold;
    }

    public function getSubtitleItalic(): string
    {
        return $this->subtitleItalic;
    }

    public function getMarginV(): float
    {
        return $this->marginV;
    }

    public function getSubtitleUnderline(): string
    {
        return $this->subtitleUnderline;
    }

    public function getSubtitleOutlineColor(): string
    {
        return $this->subtitleOutlineColor;
    }

    public function getSubtitleOutlineThickness(): int
    {
        return $this->subtitleOutlineThickness;
    }

    public function getSubtitleShadow(): int
    {
        return $this->subtitleShadow;
    }

    public function getSubtitleShadowColor(): string
    {
        return $this->subtitleShadowColor;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getSplit(): int
    {
        return $this->split;
    }

    public function setId(string $id): self
    {
        $this->id = Uuid::fromString($id);

        return $this;
    }

    public function setSubtitleFont(string $subtitleFont): self
    {
        $this->subtitleFont = $subtitleFont;

        return $this;
    }

    public function setMarginV(float $marginV): self
    {
        $this->marginV = $marginV;

        return $this;
    }

    public function setSubtitleSize(int $subtitleSize): self
    {
        $this->subtitleSize = $subtitleSize;

        return $this;
    }

    public function setSubtitleColor(string $subtitleColor): self
    {
        $this->subtitleColor = $subtitleColor;

        return $this;
    }

    public function setSubtitleBold(string $subtitleBold): self
    {
        $this->subtitleBold = $subtitleBold;

        return $this;
    }

    public function setSubtitleItalic(string $subtitleItalic): self
    {
        $this->subtitleItalic = $subtitleItalic;

        return $this;
    }

    public function setSubtitleUnderline(string $subtitleUnderline): self
    {
        $this->subtitleUnderline = $subtitleUnderline;

        return $this;
    }

    public function setSubtitleOutlineColor(string $subtitleOutlineColor): self
    {
        $this->subtitleOutlineColor = $subtitleOutlineColor;

        return $this;
    }

    public function setSubtitleOutlineThickness(int $subtitleOutlineThickness): self
    {
        $this->subtitleOutlineThickness = $subtitleOutlineThickness;

        return $this;
    }

    public function setSubtitleShadow(int $subtitleShadow): self
    {
        $this->subtitleShadow = $subtitleShadow;

        return $this;
    }

    public function setSubtitleShadowColor(string $subtitleShadowColor): self
    {
        $this->subtitleShadowColor = $subtitleShadowColor;

        return $this;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function setSplit(int $split): self
    {
        $this->split = $split;

        return $this;
    }
}
