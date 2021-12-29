<?php

namespace BogdanKharchenko\Settings;

use BogdanKharchenko\Settings\Contracts\SettingEncrypterInterface;

trait EncryptsSettings
{
    protected array $encrypted = [];

    protected SettingEncrypterInterface $encrypter;

    protected function encryptionSetup(): void
    {
        $this->encrypter = app('typed-settings.encrypter');
    }

    protected function isUsingEncryption($name): bool
    {
        return in_array($name, $this->encrypted, true);
    }
}
