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

When a setting is retrieved from the database, it will overwrite the default setting.
Create a helper function on your model, which allows us to access strongly typed settings.

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

#### Morph Map & Caching

```php
typed-settings.php

return [
    'morph'=>[
        'user-settings'=> UserSettings::class,
    ],
    'cache' => [
        'enabled' => true,
        'driver' => 'redis',
        'seconds' => 600,
    ],
];
```

#### Scopes

Sometimes you may need to check a setting on a database level, there is a helper scope available.

```php

$users = User::query()
    ->whereSettings(UserSettings::class, 'favoriteColor', 'blue')
    ->get();
```
