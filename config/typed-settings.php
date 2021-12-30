<?php

return [
    'morph' => [
        // 'user-settings' => UserSettings::class,
    ],

    'cache' => [
        'enabled' => true,
        'store' => 'redis',
        'seconds' => 600,
    ],

    'encrypter' => \BogdanKharchenko\Settings\Repository\Encrypter::class,

    'validator' => \BogdanKharchenko\Settings\Repository\Validator::class,
];
