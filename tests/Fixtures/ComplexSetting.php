<?php

namespace BogdanKharchenko\Settings\Tests\Fixtures;

use BogdanKharchenko\Settings\BaseSettings;

class ComplexSetting extends BaseSettings
{
    public string $filling = 'apple';

    public float $pi = 3.14;

    public array $ingredients = ['apple', 'flour', 'egg'];

    public int $totalPies = 2;
    
    public bool $isReady = false;
}
