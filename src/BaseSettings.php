<?php

namespace BogdanKharchenko\Settings;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
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

    /** @var static */
    protected $fluentNames;

    public function __construct(Model $model)
    {
        $this->model = $model;

        $this->encryptionSetup();

        $this->validatorSetup();

        $this->cacheSetup();

        $this->defaultSettings = $this->toArray();

        $this->setupTemporaryName();

        if (method_exists($this, 'inheritSettings')) {
            $this->inheritSettings();
        }

        $this->loadSettings();
    }

    public function saveSettings() : void
    {
        $this->preparedPayload = $this->toArray();

        $this->validator->validate($this);

        $this->model->settings()->updateOrCreate([
            'class' => ClassMorphMap::getKeyFromClass($this),
        ], [
            'payload' => $this->preparedPayload,
        ]);

        $this->wasRecentlySaved = true;

        $this->cache
            ->for($this)
            ->forgetCurrentSettings();
    }

    protected function loadSettings() : void
    {
        $settings = $this->cache
            ->for($this)
            ->cacheSettings(function () {
                return $this->model->settings()->where(
                    'class',
                    ClassMorphMap::getKeyFromClass($this)
                )->first()->payload ?? [];
            });

        $this->fillProperties($settings);
    }

    protected function getReflectedProperties() : array
    {
        $properties = new Collection((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC));

        return $properties->mapWithKeys(function (ReflectionProperty $property) {
            $name = $property->getName();
            $value = $property->getValue($this);

            if ($this->isUsingEncryption($name)) {
                $value = $this->encrypter->encrypt($value);
            }

            return [$name => $value];
        })
            ->toArray();
    }

    protected function fillProperties(array $properties = []) : self
    {
        $properties = array_merge($this->defaultSettings, $properties);

        foreach ($properties as $name => $value) {
            if ($this->isUsingEncryption($name)) {
                $value = $this->encrypter->decrypt($value);
            }

            $this->{$name} = $value;
        }

        return $this;
    }

    public function wasRecentlySaved() : bool
    {
        return $this->wasRecentlySaved;
    }

    public function getModel() : Model
    {
        return $this->model;
    }

    public function toArray() : array
    {
        return $this->getReflectedProperties();
    }

    public function getDefaultSettings() : array
    {
        return $this->defaultSettings;
    }

    /**
     * @return static
     **/
    public function toName()
    {
        return $this->fluentNames;
    }

    private function setupTemporaryName()
    {
        $keys = array_keys($this->defaultSettings);
        $combined = array_combine($keys, $keys);

        return $this->fluentNames ??= new Fluent($combined);
    }
}
