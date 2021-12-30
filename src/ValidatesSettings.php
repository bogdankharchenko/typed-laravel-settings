<?php

namespace BogdanKharchenko\Settings;

use BogdanKharchenko\Settings\Contracts\ValidatorInterface;

trait ValidatesSettings
{
    protected ValidatorInterface $validator;

    public function validatorSetup(): void
    {
        $this->validator = app('typed-settings.validator');
    }
}
