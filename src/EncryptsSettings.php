<?php

namespace BogdanKharchenko\Settings;

use BogdanKharchenko\Settings\Contracts\SettingEncrypterInterface;
use BogdanKharchenko\Settings\Repository\Encrypter;

trait EncryptsSettings
{
    protected array $encrypted = [];

    protected SettingEncrypterInterface $encrypter;

    protected function encryptionSetup(): void
    {
        $this->encrypter = app('typed-settings.encrypter');
    }

    protected function encryptSetting($value): string
    {
        return $this->encrypter->encrypt($value);
    }

    protected function decryptSetting($value)
    {
        return $this->encrypter->decrypt($value);
    }

    protected function isUsingEncryption($name): bool
    {
        return in_array($name, $this->encrypted, true);
    }
}
