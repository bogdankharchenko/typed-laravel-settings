<?php

namespace BogdanKharchenko\Settings\Tests\Fixtures;

use BogdanKharchenko\Settings\BaseSettings;
use Carbon\CarbonImmutable;

class TypedSetting extends BaseSettings
{
    public ?CarbonImmutable $sentAt = null;

    public ?DemoStatus $status = null;
}

enum DemoStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
}
