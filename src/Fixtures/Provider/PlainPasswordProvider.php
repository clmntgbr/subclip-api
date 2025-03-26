<?php

namespace App\Fixtures\Provider;

use App\Entity\ValueObject\PlainPassword;

class PlainPasswordProvider
{
    public function plainPassword(string $password): PlainPassword
    {
        $password = str_replace('\\', '', $password);

        return new PlainPassword($password);
    }
}
