<?php

namespace BogdanKharchenko\Settings\Contracts;

use BogdanKharchenko\Settings\BaseSettings;
use Closure;

interface CacheInterface
{
    public function for(BaseSettings $settings) : self;

    public function cacheSettings(Closure $closure);

    public function forgetCurrentSettings() : void;
}
