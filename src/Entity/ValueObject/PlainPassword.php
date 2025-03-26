<?php

declare(strict_types=1);

namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Embeddable]
class PlainPassword implements \Stringable
{
    #[ORM\Column(name: 'plainPassword', type: Types::STRING, length: 180, nullable: true)]
    private string $plainPassword;

    public function __construct(string $plainPassword)
    {
        Assert::string($plainPassword);
        Assert::notWhitespaceOnly($plainPassword);
        Assert::notEmpty($plainPassword);
        Assert::minLength($plainPassword, 5);
        $this->plainPassword = $plainPassword;
    }

    public function __toString(): string
    {
        return $this->plainPassword;
    }
}
