<?php

namespace App\Model\TikTok;

use Symfony\Component\Validator\Constraints as Assert;

class Callback
{
    #[Assert\NotBlank()]
    #[Assert\Type('string')]
    public ?string $code;

    #[Assert\NotBlank()]
    #[Assert\Type('string')]
    public ?string $state;
}
