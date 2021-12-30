<?php

namespace BogdanKharchenko\Settings\Repository;

use BogdanKharchenko\Settings\BaseSettings;
use BogdanKharchenko\Settings\Contracts\ValidatorInterface;

class Validator implements ValidatorInterface
{
    public function validate(BaseSettings $setting) : void
    {
        $rules = method_exists($setting, 'rules') ? $setting->rules() : [];

        if (empty($rules)) {
            return;
        }

        $messages = method_exists($setting, 'messages') ? $setting->messages() : [];

        $validator = \Illuminate\Support\Facades\Validator::make($setting->toArray(), $rules, $messages);

        $validator->validate();
    }
}
