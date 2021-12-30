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
    use ValidatesSettings;

    protected Model $model;

    protected array $defaultSettings = [];

    protected array $preparedPayload = [];

    protected bool $wasRecentlySaved = false;

    public function __construct(Model $model)
    {
        $this->model = $model;

        if (method_exists($this, 'inheritSettings')) {
            $this->inheritSettings();
        }

        $this->encryptionSetup();

        $this->validatorSetup();

        $this->defaultSettings = $this->toArray();

        $this->loadSettings();
    }

    public function saveSettings(): void
    {
        $this->preparedPayload = $this->toArray();

        $this->validator->validate($this);

        $this->model->settings()->updateOrCreate([
            'class' => ClassMorphMap::getKeyFromClass($this),
        ], [
            'payload' => $this->preparedPayload,
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
                $value = $this->encrypter->encrypt($value);
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
                    $value = $this->encrypter->decrypt($value);
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

    public function getModel(): Model
    {
        return $this->model;
    }

    public function toArray(): array
    {
        return $this->getReflectedProperties();
    }

    public function getDefaultSettings(): array
    {
        return $this->defaultSettings;
    }
}
