<?php

namespace App\Fixtures\Provider;

use App\Entity\ValueObject\Lastname;

class LastnameProvider
{
    public function lastname(string $lastname): Lastname
    {
        $lastname = str_replace('\\', '', $lastname);

        return new Lastname(value: $lastname);
    }
}
