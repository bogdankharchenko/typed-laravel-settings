<?php

namespace BogdanKharchenko\Settings\Tests;

use BogdanKharchenko\Settings\ClassMorphMap;
use BogdanKharchenko\Settings\Tests\Fixtures\ComplexSetting;

class ClassMapTest extends BaseTestCase
{
    public function test_class_map_resolves_correct_morph_mapping(): void
    {
        config(['typed-settings.morph' => ['complex' => ComplexSetting::class]]);

        $map = new ClassMorphMap();

        $this->assertEquals('complex', $map::getKeyFromClass(ComplexSetting::class));
        $this->assertEquals(ComplexSetting::class, $map::getClassFromKey('complex'));
    }

}
