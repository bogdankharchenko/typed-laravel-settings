<?php

namespace BogdanKharchenko\Settings\Tests;

use BogdanKharchenko\Settings\Models\HasSettings;
use BogdanKharchenko\Settings\Models\Setting;
use BogdanKharchenko\Settings\Tests\Fixtures\ComplexSetting;
use BogdanKharchenko\Settings\Tests\Fixtures\EncryptedSetting;
use BogdanKharchenko\Settings\Tests\Fixtures\SimpleSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsBaseTest extends BaseTestCase
{
    public function test_complex_data_types(): void
    {
        config([
            'typed-settings.morph' => [
                'complex' => ComplexSetting::class,
            ],
            'typed-settings.cache.enabled' => false,
        ]);

        $complex = new ComplexSetting($this->getUser());

        $this->assertEquals('apple', $complex->filling);
        $this->assertEquals(3.14, $complex->pi);
        $this->assertEquals([ 'apple', 'flour', 'egg' ], $complex->ingredients);
        $this->assertEquals(2, $complex->totalPies);
        $this->assertFalse($complex->isReady);

        // Update the data.
        $complex->filling = 'cherry';
        $complex->pi = 22.22;
        $complex->ingredients = [ 'cherry', 'eggs', 'butter' ];
        $complex->totalPies = 145;
        $complex->isReady = true;
        $complex->saveSettings();

        // Load Complex Data again and verify new values
        $complex = new ComplexSetting($this->getUser());
        $this->assertEquals('cherry', $complex->filling);
        $this->assertEquals(22.22, $complex->pi);
        $this->assertEquals([ 'cherry', 'eggs', 'butter' ], $complex->ingredients);
        $this->assertEquals(145, $complex->totalPies);
        $this->assertTrue($complex->isReady);

        // Test getModel returns an instance of our user.
        $this->assertTrue($complex->getModel()->is($this->getUser()));
    }

    public function test_where_setting_scope(): void
    {
        config([
            'typed-settings.morph' => [
                'complex' => ComplexSetting::class,
            ],
            'typed-settings.cache.enabled' => false,
        ]);

        $user = User::create([
            'name' => 'Alex',
            'email' => 'aaa',
            'password' => 1,
        ]);

        $complex = new ComplexSetting($user);

        // Update the data.
        $complex->totalPies = 7;
        $complex->saveSettings();

        // Test Scope
        $user = User::query()->whereSettings(ComplexSetting::class, 'totalPies', '=', 7);
        $this->assertTrue($user->exists());

        $user = User::query()->whereSettings(ComplexSetting::class, 'totalPies', '<=', 7);
        $this->assertTrue($user->exists());
    }

    public function test_settings_can_be_set_with_closure(): void
    {
        config([ 'typed-settings.morph' => [ 'complex' => ComplexSetting::class ] ]);

        $this->getUser()->setSettings(function (ComplexSetting $complex) {
            $complex->filling = 'cherry';
            $complex->pi = 22.22;
            $complex->ingredients = [ 'cherry', 'eggs', 'butter' ];
            $complex->totalPies = 145;
            $complex->isReady = true;
        });

        $complex = new ComplexSetting($this->getUser());
        $this->assertEquals('cherry', $complex->filling);
        $this->assertEquals(22.22, $complex->pi);
        $this->assertEquals([ 'cherry', 'eggs', 'butter' ], $complex->ingredients);
        $this->assertEquals(145, $complex->totalPies);
        $this->assertTrue($complex->isReady);
    }

    public function test_settings_are_cachable_and_cache_is_cleared_when_settings_are_updated(): void
    {
        config([ 'typed-settings.morph' => [ 'complex' => ComplexSetting::class ] ]);
        config([
            'typed-settings.cache' => [
                'enabled' => true,
                'store' => 'array',
                'seconds' => 30,
            ],
        ]);

        $user = $this->getUser();

        $key = implode('-', [
            $user->getMorphClass(),
            $user->getKey(),
            ComplexSetting::class,
        ]);

        // Test
        $user->getSettings(ComplexSetting::class);

        $this->assertTrue(Cache::store('array')->has($key));

        $user->setSettings(function (ComplexSetting $complex) {
            $complex->filling = 'cherry';
        });

        $this->assertFalse(Cache::store('array')->has($key));
    }

    public function test_you_may_pass_multiple_closures_when_setting(): void
    {
        $user = $this->getUser();

        config([
            'typed-settings.morph' => [
                'complex' => ComplexSetting::class,
                'simplex' => SimpleSetting::class,
            ],
            'typed-settings.cache.enabled' => false,
        ]);

        $user->setSettings(function (SimpleSetting $simple, ComplexSetting $complex) {
            $simple->favoriteColor = 'blue';
            $complex->filling = 'cherry';
        });

        $simple = new SimpleSetting($user);
        $this->assertEquals('blue', $simple->favoriteColor);

        $complex = new ComplexSetting($user);
        $this->assertEquals('cherry', $complex->filling);
    }

    public function test_settings_may_be_encrypted_and_decrypted() : void
    {
        config([
            'typed-settings.cache' => [
                'enabled' => false,
            ],
        ]);

        $encrypted = new EncryptedSetting($this->getUser());

        // Sanity Check
        $this->assertNull($encrypted->secret);
        $this->assertSame(['a','b','c'], $encrypted->list);

        $encrypted->secret = 'abc';
        $encrypted->list = ['x', 'y', 'z'];
        $encrypted->saveSettings();

        $setting = Setting::query()->first();
        $this->assertEquals('abc', Crypt::decrypt($setting->payload['secret']));
        $this->assertSame(['x','y','z'], Crypt::decrypt($setting->payload['list']));

        // Fresh Settings
        $encrypted = new EncryptedSetting($this->getUser());
        $this->assertEquals('abc', $encrypted->secret);
        $this->assertEquals(['x','y','z'], $encrypted->list);
    }

    protected function getUser(): User
    {
        $user = new User();
        $user->id = 1;

        return $user;
    }
}

class User extends Model
{
    use HasSettings;

    protected $guarded = [];
}
