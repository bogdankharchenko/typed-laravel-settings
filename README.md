### Strongly Typed Laravel Settings

#### Install

```php
composer require bogdankharchenko/typed-laravel-settings
```

#### Creating a Setting Class

```php
use \BogdanKharchenko\Settings\BaseSettings;

class UserSettings extends BaseSettings
{
    public string $favoriteColor = 'red';
}
```

#### Model Setup

```php
namespace App\Models\User;

use \Illuminate\Database\Eloquent\Model;
use \BogdanKharchenko\Settings\Models\HasSettings;

class User extends Model
{
    use HasSettings;    
}
```

#### Set Settings

```php

/** @var \App\Models\User $user */
$user = User::first();

$user->setSettings(function(UserSettings $settings){
       $settings->favoriteColor = 'blue';
});
```

#### Get Settings

```php
/** @var \App\Models\User $user */
$user = User::first();

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
