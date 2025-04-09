<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Protobuf\VideoPublishStatus;
use App\Repository\VideoPublishRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: VideoPublishRepository::class)]
#[ApiResource]
class VideoPublish
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ApiProperty(identifier: true)]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $publishId;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private string $status;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private string $message;

    public function __construct(?string $publishId = null)
    {
        $this->publishId = $publishId;
        $this->status = VideoPublishStatus::name(VideoPublishStatus::UPLOADING);
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPublishId(): ?string
    {
        return $this->publishId;
    }

    public function updateStatusError(?string $errorMessage = null)
    {
        $this->message = $errorMessage ?? 'Unknown error';
        $this->status = VideoPublishStatus::name(VideoPublishStatus::ERROR);
    }

    public function updateStatusPublished()
    {
        $this->status = VideoPublishStatus::name(VideoPublishStatus::PUBLISHED);
    }
}
