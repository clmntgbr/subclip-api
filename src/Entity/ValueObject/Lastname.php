<?php

declare(strict_types=1);

namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Embeddable]
class Lastname implements \Stringable
{
    #[ORM\Column(name: 'lastname', type: Types::STRING, nullable: false)]
    private string $lastname;

    public function __construct(string $lastname)
    {
        Assert::notEmpty($lastname);
        Assert::notWhitespaceOnly($lastname);
        Assert::string($lastname);
        $this->lastname = $lastname;
    }

    public function __toString(): string
    {
        return $this->lastname;
    }
}
