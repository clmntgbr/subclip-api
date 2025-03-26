<?php
declare(strict_types=1);
namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Token implements \Stringable
{
    #[ORM\Column(name: "value", type: Types::STRING, length: 64, nullable: true)]
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}