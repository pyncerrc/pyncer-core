<?php
namespace Pyncer\Tests\Core;

use PHPUnit\Framework\TestCase;

class StringTest extends TestCase
{
    public function testTrimString(): void
    {
        $test = \Pyncer\String\trim_string(
            '##test##',
            '##',
        );
        $result = 'test';
        $this->assertEquals($test, $result);

        $test = \Pyncer\String\trim_string(
            '##test##',
            '##',
            '%%',
        );
        $result = 'test##';
        $this->assertEquals($test, $result);

        $test = \Pyncer\String\trim_string(
            '##test%%',
            '##',
            '%%',
        );
        $result = 'test';
        $this->assertEquals($test, $result);
    }

    public function testLTrimString(): void
    {
        $test = \Pyncer\String\ltrim_string(
            '####test',
            '##',
            true,
        );
        $result = '##test';
        $this->assertEquals($test, $result);
    }
}
