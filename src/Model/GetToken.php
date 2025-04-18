<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class GetToken
{
    #[Assert\Email()]
    #[Assert\NotBlank()]
    #[Assert\Type('string')]
    public ?string $email;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 6)]
    #[Assert\Type('string')]
    public ?string $password;
}
