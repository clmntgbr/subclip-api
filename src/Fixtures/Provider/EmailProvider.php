<?php

namespace App\Fixtures\Provider;

use App\Entity\ValueObject\Email;

class EmailProvider
{
    public function email(string $email): Email
    {
        $email = str_replace('\\', '', $email);

        return new Email(value: $email);
    }
}
