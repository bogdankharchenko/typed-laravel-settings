<?php

namespace BogdanKharchenko\Settings;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

trait CachesSettings
{
    protected function cacheEnabled(): bool
    {
        return (bool)config('typed-settings.cache.enabled');
    }

    protected function cacheStore(): ?string
    {
        return config('typed-settings.cache.store');
    }

    protected function cacheSeconds(): int
    {
        return config('typed-settings.cache.seconds');
    }

    protected function cacheKey(): string
    {
        return implode('-', [
            $this->model->getMorphClass(),
            $this->model->getKey(),
            get_class($this),
        ]);
    }

    protected function forgetCurrentModelSetting(): void
    {
        $this->cache()->forget($this->cacheKey());
    }

    protected function cache(): Repository
    {
        return Cache::store($this->cacheStore());
    }

    protected function cacheSettings(Closure $closure)
    {
        if ($this->cacheEnabled()) {
            return $this->cache()->remember($this->cacheKey(), $this->cacheSeconds(), fn () => $closure());
        }

        return $closure();
    }
}
