<?php

declare(strict_types=1);

namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Embeddable]
class Status implements \Stringable
{
    #[ORM\Column(name: 'status', type: Types::STRING, length: 180, nullable: false)]
    private string $status;

    public function __construct(string $status)
    {
        Assert::notEmpty($status);
        Assert::notWhitespaceOnly($status);
        Assert::string($status);
        $this->status = $status;
    }

    public function __toString(): string
    {
        return $this->status;
    }
}
