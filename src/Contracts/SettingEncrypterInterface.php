<?php

namespace BogdanKharchenko\Settings\Contracts;

interface SettingEncrypterInterface
{
    public function encrypt($value);

    public function decrypt($value);
}
