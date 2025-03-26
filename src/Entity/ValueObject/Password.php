<?php

declare(strict_types=1);

namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Embeddable]
class Password implements \Stringable
{
    #[ORM\Column(name: 'password', type: Types::STRING, length: 180, nullable: false)]
    private string $password;

    public function __construct(string $password)
    {
        Assert::notEmpty($password);
        Assert::notWhitespaceOnly($password);
        Assert::string($password);
        $this->password = $password;
    }

    public function __toString(): string
    {
        return $this->password;
    }
}
