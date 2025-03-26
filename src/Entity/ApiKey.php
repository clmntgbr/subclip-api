<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\ApiKeyRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

const API_KEY_READ = 'user.read';
const API_KEY_WRITE = 'user.write';

#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[ApiResource]
class ApiKey
{
    use TimestampableEntity;
    
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ApiProperty(identifier: true)]
    private Uuid $id;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $token = null;

    #[ORM\OneToOne(targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expireAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function eraseToken(): void
    {
        $this->token = null;
    }

    public function setToken(string $token): static
    {
        $token = hash('sha256', $token);
        $this->token = $token;

        return $this;
    }

    public function getExpireAt(): ?\DateTimeImmutable
    {
        return $this->expireAt;
    }

    public function setExpireAt(?\DateTimeImmutable $expireAt): static
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }
}
