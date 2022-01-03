<?php

namespace BogdanKharchenko\Settings\Tests;

use BogdanKharchenko\Settings\Models\HasSettings;
use BogdanKharchenko\Settings\Models\Setting;
use BogdanKharchenko\Settings\Repository\Encrypter;
use BogdanKharchenko\Settings\Tests\Fixtures\ComplexSetting;
use BogdanKharchenko\Settings\Tests\Fixtures\EncryptedSetting;
use BogdanKharchenko\Settings\Tests\Fixtures\SimpleSetting;
use BogdanKharchenko\Settings\Tests\Fixtures\SimpleSettingWithRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class SettingsBaseTest extends BaseTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        config([
            'typed-settings.cache.enabled' => false,
            'typed-settings.morph' => [
                'simplex-with-rule' => SimpleSettingWithRule::class,
                'complex' => ComplexSetting::class,
                'simple' => SimpleSetting::class,
            ],
        ]);
    }

    public function test_complex_data_types() : void
    {
        $complex = new ComplexSetting($this->getUser());

        $this->assertEquals('apple', $complex->filling);
        $this->assertEquals(3.14, $complex->pi);
        $this->assertEquals(['apple', 'flour', 'egg'], $complex->ingredients);
        $this->assertEquals(2, $complex->totalPies);
        $this->assertFalse($complex->isReady);

        // Update the data.
        $complex->filling = 'cherry';
        $complex->pi = 22.22;
        $complex->ingredients = ['cherry', 'eggs', 'butter'];
        $complex->totalPies = 145;
        $complex->isReady = true;
        $complex->saveSettings();

        // Load Complex Data again and verify new values
        $complex = new ComplexSetting($this->getUser());
        $this->assertEquals('cherry', $complex->filling);
        $this->assertEquals(22.22, $complex->pi);
        $this->assertEquals(['cherry', 'eggs', 'butter'], $complex->ingredients);
        $this->assertEquals(145, $complex->totalPies);
        $this->assertTrue($complex->isReady);

        // Test getModel returns an instance of our user.
        $this->assertTrue($complex->getModel()->is($this->getUser()));

        $this->assertEquals(3.14, $complex->getDefaultSettings()['pi']);
        $this->assertEquals('apple', $complex->getDefaultSettings()['filling']);
    }

    public function test_where_setting_scope() : void
    {
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

    public function test_settings_can_be_set_with_closure() : void
    {
        $this->getUser()->setSettings(function (ComplexSetting $complex) {
            $complex->filling = 'cherry';
            $complex->pi = 22.22;
            $complex->ingredients = ['cherry', 'eggs', 'butter'];
            $complex->totalPies = 145;
            $complex->isReady = true;
        });

        $complex = new ComplexSetting($this->getUser());
        $this->assertEquals('cherry', $complex->filling);
        $this->assertEquals(22.22, $complex->pi);
        $this->assertEquals(['cherry', 'eggs', 'butter'], $complex->ingredients);
        $this->assertEquals(145, $complex->totalPies);
        $this->assertTrue($complex->isReady);
    }

    public function test_settings_are_cachable_and_cache_is_cleared_when_settings_are_updated() : void
    {
        config([
            'typed-settings.cache' => [
                'enabled' => true,
                'store' => 'array',
                'seconds' => 30,
            ],
        ]);

        $user = $this->getUser();

        $complexKey = implode('-', [
            $user->getMorphClass(),
            $user->getKey(),
            ComplexSetting::class,
        ]);

        $simpleKey = implode('-', [
            $user->getMorphClass(),
            $user->getKey(),
            SimpleSetting::class,
        ]);

        // Test
        $complex = new ComplexSetting($user);
        $simple = new SimpleSetting($user);

        // Keys Exist when loaded
        $this->assertTrue(Cache::store('array')->has($complexKey));
        $this->assertTrue(Cache::store('array')->has($simpleKey));

        $user->setSettings(function (ComplexSetting $complex, SimpleSetting $simple) {
            $complex->filling = 'cherry';
            $simple->favoriteColor = 'blue';
        });

        // The Cache was cleared.
        $this->assertFalse(Cache::store('array')->has($complexKey));
        $this->assertFalse(Cache::store('array')->has($simpleKey));

        // Check if the setting has changed. -- refresh
        $complex = new ComplexSetting($user);
        $simple = new SimpleSetting($user);

        $this->assertEquals('cherry', $complex->filling);
        $this->assertEquals('blue', $simple->favoriteColor);
    }

    public function test_you_may_pass_multiple_closures_when_setting() : void
    {
        $user = $this->getUser();

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
        $this->assertSame(['a', 'b', 'c'], $encrypted->list);

        $encrypted->secret = 'abc';
        $encrypted->list = ['x', 'y', 'z'];
        $encrypted->saveSettings();

        $defaultEncrypter = new Encrypter();

        $setting = Setting::query()->first();
        $this->assertEquals('abc', $defaultEncrypter->decrypt($setting->payload['secret']));
        $this->assertSame(['x', 'y', 'z'], $defaultEncrypter->decrypt($setting->payload['list']));

        // Fresh Settings
        $encrypted = new EncryptedSetting($this->getUser());
        $this->assertEquals('abc', $encrypted->secret);
        $this->assertEquals(['x', 'y', 'z'], $encrypted->list);
    }

    public function test_it_validates_against_rules_if_provided() : void
    {
        $user = $this->getUser();

        try {
            $user->setSettings(function (SimpleSettingWithRule $simple) {
                $simple->favoriteColor = 'blue';
            });
        } catch (ValidationException $exception) {
            $this->assertContains('color does not match', $exception->errors()['favoriteColor']);

            $this->assertFalse(Arr::has($exception->errors(), 'leastFavoriteColor'));
        }
    }

    public function test_you_can_get_the_property_name_by_calling_byName_function() : void
    {
        config(['typed-settings.morph' => ['complex' => ComplexSetting::class]]);

        $complex = new ComplexSetting($this->getUser());

        $this->assertEquals('filling', $complex->toName()->filling);
        $this->assertEquals('pi', $complex->toName()->pi);
        $this->assertEquals('isReady', $complex->toName()->isReady);
    }

    protected function getUser() : User
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

    public function complex() : ComplexSetting
    {
        return new ComplexSetting($this);
    }
}
