<?php

declare(strict_types=1);

namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Embeddable]
class SociaAccountId implements \Stringable
{
    #[ORM\Column(name: 'social_account_id', type: Types::STRING, nullable: false)]
    private string $value;

    public function __construct(string $value)
    {
        Assert::notEmpty($value);
        Assert::notWhitespaceOnly($value);
        Assert::string($value);
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
