<?php

namespace BogdanKharchenko\Settings;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
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

            $this->{$name} = $this->castPropertyValue($name, $value);
        }

        return $this;
    }

    /**
     * Hydrate a payload value back into the type the property declares.
     *
     * The payload column stores JSON, so anything that survives a
     * `json_encode` / `json_decode` round-trip comes back as a scalar,
     * array, or null — never the original object. For declared object
     * types we cast back here so subclasses don't have to override
     * fillProperties just to recover CarbonImmutable, DateTime, or
     * backed-enum instances.
     *
     * Override in a subclass to handle custom value-object types; call
     * `parent::castPropertyValue()` to keep the built-in casts.
     */
    protected function castPropertyValue(string $name, mixed $value) : mixed
    {
        if ($value === null) {
            return null;
        }

        $type = $this->reflectedPropertyType($name);
        if ($type === null) {
            return $value;
        }

        // Idempotent: a freshly-set property already holds the right type
        // (we run through this path on the in-memory default merge too).
        if (is_object($value) && $value instanceof $type) {
            return $value;
        }

        if (is_string($value) && (
            $type === DateTimeInterface::class
            || is_subclass_of($type, DateTimeInterface::class)
        )) {
            return method_exists($type, 'parse')
                ? $type::parse($value)
                : new $type($value);
        }

        if (is_subclass_of($type, BackedEnum::class)) {
            return $type::tryFrom($value) ?? $value;
        }

        return $value;
    }

    /**
     * Resolve the single-class type a property declares, or null when
     * the property is untyped, scalar, mixed, union, or intersection.
     *
     * Cached at the class+property granularity — reflection metadata is
     * immutable so this stays valid across Octane requests.
     */
    private function reflectedPropertyType(string $name) : ?string
    {
        static $cache = [];

        $key = static::class . '::' . $name;

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $property = new ReflectionProperty(static::class, $name);
        } catch (ReflectionException) {
            return $cache[$key] = null;
        }

        $type = $property->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            return $cache[$key] = $type->getName();
        }

        return $cache[$key] = null;
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
