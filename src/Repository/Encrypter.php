<?php

namespace BogdanKharchenko\Settings\Repository;

use BogdanKharchenko\Settings\Contracts\EncrypterInterface;
use Illuminate\Support\Facades\Crypt;

class Encrypter implements EncrypterInterface
{
    public function encrypt($value): string
    {
        return Crypt::encrypt($value);
    }

    public function decrypt($value)
    {
        return Crypt::decrypt($value);
    }
}
