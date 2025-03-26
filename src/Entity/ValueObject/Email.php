<?php
declare(strict_types=1);
namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Embeddable]
class Email implements \Stringable
{
    #[ORM\Column(name: "token", type: Types::STRING, length: 180, nullable: false, unique: true)]
    private string $value;

    public function __construct(string $value)
    {
        Assert::notEmpty($value);
        Assert::notWhitespaceOnly($value);
        Assert::email($value);
        Assert::string($value);
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}