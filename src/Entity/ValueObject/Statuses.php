<?php

declare(strict_types=1);

namespace App\Entity\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

#[ORM\Embeddable]
class Statuses
{
    #[ORM\Column(name: 'statuses', type: Types::JSON, nullable: false)]
    private array $value;

    public function __construct(array $value = [])
    {
        Assert::isArray($value);
        $this->value = $value;
    }

    public function getValues(): array
    {
        return $this->value;
    }
}
