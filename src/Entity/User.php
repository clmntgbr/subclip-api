<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\ValueObject\Email;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

const USER_READ = 'user.read';
const USER_WRITE = 'user.write';

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email.value'])]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/me',
            normalizationContext: ['skip_null_values' => false, 'groups' => [USER_READ]],
        )
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;
    
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ApiProperty(identifier: true)]
    #[Groups([USER_READ])]
    private Uuid $id;

    #[Embedded(class: Email::class)]
    private ?Email $email;

    #[ORM\Column]
    #[Groups([USER_READ])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $plainPassword = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([USER_READ])]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups([USER_READ])]
    private ?string $lastName = null;

    #[ORM\OneToOne(targetEntity: ApiKey::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'api_key_id', referencedColumnName: 'id', nullable: true)]
    private ?ApiKey $apiKey = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    public function setApiKey(?ApiKey $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    #[Groups([USER_READ])]
    #[SerializedName('email')]
    public function getApiEmail()
    {
        return $this->email->__toString();
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(Email $email): static
    {
        $this->email = $email;

        return $this;
    }
}
