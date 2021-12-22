<?php

namespace BogdanKharchenko\Settings;

use Illuminate\Support\Facades\Crypt;

trait EncryptsSettings
{
    protected array $encrypted = [];

    protected function encryptSetting($value): string
    {
        return Crypt::encrypt($value);
    }

    protected function decryptSetting($value)
    {
        return Crypt::decrypt($value);
    }

    protected function isUsingEncryption($name): bool
    {
        return in_array($name, $this->encrypted, true);
    }
}
