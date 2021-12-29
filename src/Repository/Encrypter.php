<?php

namespace BogdanKharchenko\Settings\Repository;

use BogdanKharchenko\Settings\Contracts\SettingEncrypterInterface;
use Illuminate\Support\Facades\Crypt;

class Encrypter implements SettingEncrypterInterface
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
