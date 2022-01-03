<?php

namespace BogdanKharchenko\Settings\Repository;

use BogdanKharchenko\Settings\BaseSettings;
use BogdanKharchenko\Settings\Contracts\CacheInterface;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;

class Cacher implements CacheInterface
{
    protected BaseSettings $settings;

    protected Model $model;

    public function init(BaseSettings $settings) : void
    {
        $this->settings = $settings;

        $this->model = $settings->getModel();
    }

    public function cacheSettings(Closure $closure)
    {
        if ($this->cacheEnabled()) {
            return $this->cache()->remember($this->cacheKey(), $this->cacheSeconds(), fn() => $closure());
        }

        return $closure();
    }

    public function forgetCurrentSettings() : void
    {
        $this->cache()->forget($this->cacheKey());
    }

    protected function cacheEnabled() : bool
    {
        return (bool) config('typed-settings.cache.enabled');
    }

    protected function cacheStore() : ?string
    {
        return config('typed-settings.cache.store');
    }

    protected function cacheSeconds() : int
    {
        return config('typed-settings.cache.seconds');
    }

    protected function cacheKey() : string
    {
        return implode('-', [
            $this->model->getMorphClass(),
            $this->model->getKey(),
            get_class($this->settings),
        ]);
    }

    protected function cache() : Repository
    {
        return \Illuminate\Support\Facades\Cache::store($this->cacheStore());
    }
}
