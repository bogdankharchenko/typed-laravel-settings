<?php

namespace BogdanKharchenko\Settings\Tests\Fixtures;

use BogdanKharchenko\Settings\BaseSettings;
use Illuminate\Validation\Rule;

class SimpleSettingWithRule extends BaseSettings
{
    public string $favoriteColor = 'red';

    public string $leastFavoriteColor = 'orange';

    public function rules()
    {
        return [
            'favoriteColor' => ['required', Rule::in(['red', 'green'])],
        ];
    }

    public function messages()
    {
        return [
            'favoriteColor.in' => 'color does not match',
        ];
    }
}
