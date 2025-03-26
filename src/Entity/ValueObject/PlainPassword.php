<?php

declare(strict_types=1);

namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Embeddable]
class PlainPassword implements \Stringable
{
    #[ORM\Column(name: 'value', type: Types::STRING, length: 180, nullable: true, unique: false)]
    private string $value;

    public function __construct(string $value)
    {
        Assert::string($value);
        Assert::notWhitespaceOnly($value);
        Assert::notEmpty($value);
        Assert::minLength($value, 5);
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
