<?php

namespace App\Fixtures\Provider;

use App\Entity\ValueObject\Firstname;

class FirstnameProvider
{
    public function firstname(string $firstname): Firstname
    {
        $firstname = str_replace('\\', '', $firstname);

        return new Firstname($firstname);
    }
}
