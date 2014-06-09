<?php
namespace Hyperion\Tests\Framework\Utility;

use Hyperion\Tests\Framework\Resources\Utility\ConfigurableClass;

class ConfigTraitTest extends \PHPUnit_Framework_TestCase {

    /**
     * @small
     */
    public function testConfigTrait()
    {
        $obj = new ConfigurableClass();
        $obj->init([
                'alpha' => 100,
                'bravo' => [
                    'charlie' => 'Hello World',
                    'delta' => null
                ]
            ]);

        // Init values
        $this->assertEquals(100, $obj->get('alpha'));
        $this->assertCount(2, $obj->get('bravo'));
        $this->assertEquals('Hello World', $obj->get('bravo.charlie'));
        $this->assertEquals('Hello World', $obj->get('bravo-charlie', null, '-'));
        $this->assertNull($obj->get('bravo.delta'));

        // Default
        $this->assertEquals('poop', $obj->get('invalid', 'poop'));
        $this->assertEquals('poop', $obj->get('invalid.item', 'poop'));
        $this->assertNull($obj->get('invalid'));
        $this->assertNull($obj->get('invalid.item'));

        // Set
        $obj->set('invalid.item', 'stuff');
        $this->assertEquals('stuff', $obj->get('invalid.item', 'poop'));

        $obj->set('bravo.delta', 'boobies');
        $this->assertEquals('boobies', $obj->get('bravo.delta'));

        // Setter didn't corrupt anything
        $this->assertEquals(100, $obj->get('alpha'));
        $this->assertCount(2, $obj->get('bravo'));
        $this->assertEquals('Hello World', $obj->get('bravo.charlie'));
        $this->assertEquals('Hello World', $obj->get('bravo-charlie', null, '-'));

        $obj->set('bravo', 123);
        $this->assertEquals(100, $obj->get('alpha'));
        $this->assertEquals(123, $obj->get('bravo'));

    }

}
 