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

    #[Embedded(class: Email::class)]
    private Email $email;

    #[Embedded(class: Password::class)]
    private Password $password;

    #[Embedded(class: PlainPassword::class)]
    private ?PlainPassword $plainPassword;

    #[ORM\Column]
    #[Groups([USER_READ])]
    private array $roles = [];

    #[Embedded(class: Lastname::class)]
    private Lastname $lastname;

    #[Embedded(class: Firstname::class)]
    private Firstname $firstname;

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
        return $this->password->__toString();
    }

    public function setPassword(Password $password): static
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

    #[Groups([USER_READ])]
    #[SerializedName('email')]
    public function getApiEmail()
    {
        return $this->email->__toString();
    }

    #[Groups([USER_READ])]
    #[SerializedName('firstname')]
    public function getApiFirstname()
    {
        return $this->firstname->__toString();
    }

    #[Groups([USER_READ])]
    #[SerializedName('lastname')]
    public function getApiLastname()
    {
        return $this->lastname->__toString();
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

    public function updateToken(User $user, string $token): User
    {
        if (null === $user->getApiKey()) {
            $apiKey = new ApiKey();
            $apiKey->setUser($user);
            $user->setApiKey($apiKey);
        }

        $user->getApiKey()->setToken($token);
        $user->getApiKey()->setExpireAt(new \DateTimeImmutable('+7 days'));

        return $user;
    }
}
