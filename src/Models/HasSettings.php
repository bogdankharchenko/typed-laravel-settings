<?php

namespace BogdanKharchenko\Settings\Models;

use BogdanKharchenko\Settings\BaseSettings;
use BogdanKharchenko\Settings\ClassMorphMap;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Traits\ReflectsClosures;

trait HasSettings
{
    use ReflectsClosures;

    public function settings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'settable');
    }

    public function setSettings(Closure $closure): self
    {
        $closureParameterTypes = $this->closureParameterTypes($closure);

        $settings = [];

        foreach ($closureParameterTypes as $key => $class) {
            /** @var BaseSettings $setting */
            $settings[] = new $class($this);
        }

        $closure(...$settings);

        /** @var BaseSettings $baseSetting */
        foreach ($settings as $baseSetting) {
            if (false === $baseSetting->wasRecentlySaved()) {
                $baseSetting->saveSettings();
            }
        }

        return $this;
    }

    public function getSettings($class)
    {
        return new $class($this);
    }

    public function scopeWhereSettings(Builder $builder, $class, $name, $operator, $value = null)
    {
        $class = ClassMorphMap::getKeyFromClass($class);

        $builder->whereHas('settings', function ($builder) use ($class, $name, $operator, $value) {
            $builder->where('class', $class);

            $builder->where(function (Builder $builder) use ($name, $operator, $value) {
                $builder->where("payload->{$name}", $operator ?? '=', $value);
            });
        });

        return $builder;
    }
}
