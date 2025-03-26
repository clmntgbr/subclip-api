<?php
declare(strict_types=1);
namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Embeddable]
class Email implements \Stringable
{
    #[ORM\Column(name: "email", type: Types::STRING, length: 180, nullable: false, unique: true)]
    private string $email;

    public function __construct(string $email)
    {
        Assert::notEmpty($email);
        Assert::notWhitespaceOnly($email);
        Assert::email($email);
        Assert::string($email);
        $this->email = $email;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}