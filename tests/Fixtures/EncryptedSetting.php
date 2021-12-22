<?php

namespace BogdanKharchenko\Settings\Tests\Fixtures;

use BogdanKharchenko\Settings\BaseSettings;

class EncryptedSetting extends BaseSettings
{

    protected array $encrypted = [
        'secret',
        'list',
    ];

    public ?string $secret = null;

    public array $list = [
        'a',
        'b',
        'c',
    ];
}
