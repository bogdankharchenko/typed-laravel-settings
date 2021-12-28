<?php

namespace BogdanKharchenko\Settings;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionProperty;

abstract class BaseSettings implements Arrayable
{
    use CachesSettings;

    protected Model $model;

    protected array $defaultSettings = [];

    protected bool $wasRecentlySaved = false;

    public function __construct(Model $model)
    {
        $this->model = $model;

        if (method_exists($this, 'inheritSettings')) {
            $this->inheritSettings();
        }

        $this->defaultSettings = $this->getReflectedProperties();

        $this->loadSettings();
    }

    public function saveSettings(): void
    {
        $this->model->settings()->updateOrCreate([
            'class' => ClassMorphMap::getKeyFromClass($this),
        ], [
            'payload' => $this->toArray(),
        ]);

        $this->wasRecentlySaved = true;

        $this->forgetCurrentModelSetting();
    }

    protected function loadSettings(): void
    {
        $settings = $this->cacheSettings(function () {
            return $this->model->settings()->where(
                'class',
                ClassMorphMap::getKeyFromClass($this)
            )->first()->payload ?? [];
        });

        $this->fillProperties($settings);
    }

    protected function getReflectedProperties(): array
    {
        $properties = new Collection((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC));

        return $properties->mapWithKeys(function (ReflectionProperty $property) {
            return [ $property->getName() => $property->getValue($this) ];
        })->toArray();
    }

    protected function fillProperties(array $properties = []): self
    {
        foreach ($properties as $name => $value) {
            if (array_key_exists($name, $this->defaultSettings)) {
                $this->{$name} = $value;
            }
        }

        return $this;
    }

    public function wasRecentlySaved(): bool
    {
        return $this->wasRecentlySaved;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function toArray(): array
    {
        return $this->getReflectedProperties();
    }
}
