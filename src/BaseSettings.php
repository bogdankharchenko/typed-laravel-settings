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
    use EncryptsSettings;

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
            'payload' => $this->getReflectedProperties(),
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
            $name = $property->getName();
            $value = $property->getValue($this);

            if ($this->isUsingEncryption($name)) {
                $value = $this->encryptSetting($value);
            }

            return [ $name => $value ];
        })
            ->toArray();
    }

    protected function fillProperties(array $properties = []): self
    {
        foreach ($properties as $name => $value) {
            if (array_key_exists($name, $this->defaultSettings)) {
                if ($this->isUsingEncryption($name)) {
                    $value = $this->decryptSetting($value);
                }

                $this->{$name} = $value;
            }
        }

        return $this;
    }

    public function wasRecentlySaved(): bool
    {
        return $this->wasRecentlySaved;
    }

    public function toArray(): array
    {
        return $this->getReflectedProperties();
    }
}
