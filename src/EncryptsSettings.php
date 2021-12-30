<?php

namespace BogdanKharchenko\Settings;

use BogdanKharchenko\Settings\Contracts\EncrypterInterface;

trait EncryptsSettings
{
    protected array $encrypted = [];

    protected EncrypterInterface $encrypter;

    protected function encryptionSetup(): void
    {
        $this->encrypter = app('typed-settings.encrypter');
    }

    protected function isUsingEncryption($name): bool
    {
        return in_array($name, $this->encrypted, true);
    }
}
