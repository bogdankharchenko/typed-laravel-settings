<?php

namespace BogdanKharchenko\Settings\Contracts;

use BogdanKharchenko\Settings\BaseSettings;
use Closure;

interface CacheInterface
{
    public function init(BaseSettings $settings) : void;

    public function cacheSettings(Closure $closure);

    public function forgetCurrentSettings() : void;
}
