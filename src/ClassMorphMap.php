<?php

namespace BogdanKharchenko\Settings;

class ClassMorphMap
{
    public static function getKeyFromClass($setting): string
    {
        if (is_object($setting)) {
            $setting = get_class($setting);
        }

        $result = array_search($setting, static::morphMapConfig(), true);

        return $result ?? $setting;
    }

    public static function getClassFromKey($key)
    {
        return static::morphMapConfig()[$key];
    }

    public static function morphMapConfig(): array
    {
        return config('typed-settings.morph');
    }
}
