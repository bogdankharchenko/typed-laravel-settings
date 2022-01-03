<?php

namespace BogdanKharchenko\Settings;

use BogdanKharchenko\Settings\Contracts\ValidatorInterface;

trait ValidatesSettings
{
    protected ValidatorInterface $validator;

    protected function validatorSetup(): void
    {
        $this->validator = app('typed-settings.validator');
    }
}
