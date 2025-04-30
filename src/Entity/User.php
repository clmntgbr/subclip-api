<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\ValueObject\Email;
use App\Entity\ValueObject\Firstname;
use App\Entity\ValueObject\Lastname;
use App\Entity\ValueObject\Password;
use App\Entity\ValueObject\PlainPassword;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
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
            normalizationContext: ['skip_null_values' => false, 'groups' => [USER_READ, SOCIAL_ACCOUNT_READ]],
        ),
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

    #[Embedded(class: Email::class, columnPrefix: false)]
    #[Groups([USER_READ])]
    private Email $email;

    #[Embedded(class: Password::class, columnPrefix: false)]
    private Password $password;

    #[Embedded(class: PlainPassword::class, columnPrefix: false)]
    private ?PlainPassword $plainPassword;

    #[ORM\Column]
    #[Groups([USER_READ])]
    private array $roles = [];

    #[Embedded(class: Lastname::class, columnPrefix: false)]
    #[Groups([USER_READ])]
    private Lastname $lastname;

    #[Embedded(class: Firstname::class, columnPrefix: false)]
    #[Groups([USER_READ])]
    private Firstname $firstname;

    #[ORM\Column(name: 'state', type: Types::STRING, length: 180, nullable: true, unique: true)]
    private string $state;

    #[OneToMany(targetEntity: SocialAccount::class, mappedBy: 'user')]
    #[Groups([USER_READ])]
    private Collection $socialAccounts;

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
        return $this->password->__toString();
    }

    public function setPassword(Password $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = Uuid::fromString($id);

        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getPlainPassword(): ?PlainPassword
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(PlainPassword $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getFirstname(): Firstname
    {
        return $this->firstname;
    }

    public function setFirstname(Firstname $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): Lastname
    {
        return $this->lastname;
    }

    public function setLastname(Lastname $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
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

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getSocialAccounts(): Collection
    {
        return $this->socialAccounts;
    }
}
