<?php
namespace Pyncer\Tests\Core;

use PHPUnit\Framework\TestCase;

class UtilityTest extends TestCase
{
    public function testToPascalCase(): void
    {
        $expected = 'ThisIsATest';

        $result = \Pyncer\Utility\to_pascal_case('ThisIsATest');
        $this->assertEquals($result, $expected);

        $result = \Pyncer\Utility\to_pascal_case('this_is_a_test');
        $this->assertEquals($result, $expected);

        $result = \Pyncer\Utility\to_pascal_case('thisIsATest');
        $this->assertEquals($result, $expected);
    }

    public function testToCamelCase(): void
    {
        $expected = 'thisIsATest';

        $result = \Pyncer\Utility\to_camel_case('ThisIsATest');
        $this->assertEquals($result, $expected);

        $result = \Pyncer\Utility\to_camel_case('this_is_a_test');
        $this->assertEquals($result, $expected);

        $result = \Pyncer\Utility\to_camel_case('thisIsATest');
        $this->assertEquals($result, $expected);
    }

    public function testToSnakeCase(): void
    {
        $expected = 'this_is_a_test';

        $result = \Pyncer\Utility\to_snake_case('ThisIsATest');
        $this->assertEquals($result, $expected);

        $result = \Pyncer\Utility\to_snake_case('this_is_a_test');
        $this->assertEquals($result, $expected);

        $result = \Pyncer\Utility\to_snake_case('thisIsATest');
        $this->assertEquals($result, $expected);
    }
}
