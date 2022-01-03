[![run-tests](https://github.com/bogdankharchenko/typed-laravel-settings/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/bogdankharchenko/typed-laravel-settings/actions/workflows/run-tests.yml)
[![codecov](https://codecov.io/gh/bogdankharchenko/typed-laravel-settings/branch/main/graph/badge.svg?token=8OSEIM0L18)](https://codecov.io/gh/bogdankharchenko/typed-laravel-settings)

### Strongly Typed Laravel Settings

#### Install

```php
composer require bogdankharchenko/typed-laravel-settings
```

#### Model Setup

```php
namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use BogdanKharchenko\Settings\Models\HasSettings;

class User extends Model
{
    use HasSettings;    
}
```

#### Creating a Setting Class

This class represents a group of available settings for a given model.
Public properties and their values will automatically be serialized into a json column, and serve as defaults.

```php
use BogdanKharchenko\Settings\BaseSettings;

class UserSettings extends BaseSettings
{
    public string $favoriteColor = 'red';
}
```

#### Set Settings

Changing the values will persist them into the database. When updating settings, cache will automatically be flushed.

```php
$user = User::first();

$user->setSettings(function(UserSettings $settings){
    $settings->favoriteColor = 'blue';
});

// Updating Multiple Settings

$user->setSettings(function(UserSettings $settings, EmailPreferences $emailPreferences){
    $settings->favoriteColor = 'blue';

    $emailPreferences->marketing = false;
});

// Using the `setSettings()` Closure is a wrapper around the code example below.
$settings = new UserSettings($user);

$settings->favoriteColor = 'pink';

$settings->saveSettings();


```

#### Get Settings

Creating a helper function on your allows us to access strongly typed settings.
When a setting is retrieved from the database, it will overwrite the default setting on the class.


```php
class User extends Model 
{
    public function config(): UserSettings
    {
        return new UserSettings($this);      
    }
}

$user->config()->favoriteColor // returns blue

// Alternatively you can use docblocks to assist in typing

/** @var UserSettings $settings */
$settings = $user->getSettings(UserSettings::class);

$settings->favoriteColor // returns blue

```

#### Setting Value Encryption
You may decide to encrypt/decrypt sensitive settings such as secrets.   You should specify a `protected array $encrypted` with a list of properties to encrypt/decrypt. Data will be encrypted into the database, and decrypted when retrieved.
```php
use BogdanKharchenko\Settings\BaseSettings;

class UserSettings extends BaseSettings
{
    protected array $encrypted = [
        'secret',
        'list',
    ];

    public ?string $secret = null;

    public array $list = [
        'a',
        'b',
        'c',
    ];
}
```
The default encrypter is `BogdanKharchenko\Settings\Repository\Encrypter` but you may choose to implement your own encryption strategy by implementing `BogdanKharchenko\Settings\Contracts\EncrypterInterface` and changing the config.

#### Validation
You can define validation rules in your custom `Settings` class, in the same way as you would in `FormRequests` using the `rules()` and `messages()` methods. These rules will be triggered immediately before attempting to persist the data into the database.
```php
class SimpleSettingWithRule extends BaseSettings
{
    public string $favoriteColor = 'red';

    // Optional
    public function rules()
    {
        return [
            'favoriteColor' => ['required', Rule::in(['red', 'green'])],
            
            // Or
            $this->toName()->favoriteColor => ['required', Rule::in(['red', 'green'])],
        ];
    }

    // Optional 
    public function messages()
    {
        return [
            'favoriteColor.in' => 'color does not match',
        ];
    }
}
```
Validation is optional, as you may choose to do this in other parts of your app.  You can also customize the validator by implementing the `BogdanKharchenko\Settings\Contracts\ValidatorInterface` and changing the `validator` config.

#### Morph Map & Caching
Similarly to morph map for Eloquent, it is a good idea to allow your Setting be more flexible to restructuring without touching your database.
```php
// config/typed-settings.php

return [
    'morph'=>[
        'user-settings'=> UserSettings::class,
    ],
    'cache' => [
        'enabled' => true,
        'store' => 'redis',
        'seconds' => 600,
    ],
    
   'encrypter' => \BogdanKharchenko\Settings\Repository\Encrypter::class,
   'validator' => \BogdanKharchenko\Settings\Repository\Validator::class,
];
```

#### Scopes

Sometimes you may need to check a setting on a database level, there is a helper scope available.

```php

$users = User::query()
    ->whereSettings(UserSettings::class, 'favoriteColor', 'blue')
    ->get();
```
