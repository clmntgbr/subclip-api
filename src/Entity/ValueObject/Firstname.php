<?php

declare(strict_types=1);

namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Embeddable]
class Firstname implements \Stringable
{
    #[ORM\Column(name: 'firstname', type: Types::STRING, nullable: false)]
    private string $firstname;

    public function __construct(string $firstname)
    {
        Assert::notEmpty($firstname);
        Assert::notWhitespaceOnly($firstname);
        Assert::string($firstname);
        $this->firstname = $firstname;
    }

    public function __toString(): string
    {
        return $this->firstname;
    }
}
