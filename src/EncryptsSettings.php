<?php

namespace BogdanKharchenko\Settings;

use Illuminate\Support\Facades\Crypt;

trait EncryptsSettings
{

    protected function encryptSetting($value): string
    {
        return Crypt::encrypt($value);
    }

    protected function decryptSetting($value)
    {
        return Crypt::decrypt($value);
    }

    protected function isEncrypted($name): bool
    {
        return in_array($name, $this->encrypted ?? [], true);
    }
}
