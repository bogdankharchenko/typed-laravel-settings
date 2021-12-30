<?php

namespace BogdanKharchenko\Settings\Contracts;

use BogdanKharchenko\Settings\BaseSettings;

interface ValidatorInterface
{
    public function validate(BaseSettings $setting) : void;
}
