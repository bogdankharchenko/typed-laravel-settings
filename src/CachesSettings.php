<?php

namespace BogdanKharchenko\Settings;

use BogdanKharchenko\Settings\Contracts\CacheInterface;

trait CachesSettings
{
    protected CacheInterface $cache;

    protected function cacheSetup() : void
    {
        /** @var CacheInterface $cacher */
        $cacher = app('typed-settings.cacher');

        $this->cache = $cacher;
    }
}
