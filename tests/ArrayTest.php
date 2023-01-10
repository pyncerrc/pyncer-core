<?php
namespace Pyncer\Tests\Core;

use PHPUnit\Framework\TestCase;

class ArrayTest extends TestCase
{
    public function testNullify(): void
    {
        $test = \Pyncer\Array\nullify(
            [],
            'test',
        );
        $result = 'test';
        $this->assertEquals($test, $result);
    }

    public function testMergeSafe(): void
    {
        $test = \Pyncer\Array\merge_safe(
            [
                'a',
                'b',
                'c' => 'd',
                'e' => 'f',
            ],
            [
                'a',
                'c',
                'c' => 'd',
                'g' => 'h',
            ],
        );
        $result = [
            'a',
            'b',
            'd',
            'f',
            'a',
            'c',
            'd',
            'h',
        ];
        $this->assertEquals($test, $result);
    }

    public function testMergeDiff(): void
    {
        $test = \Pyncer\Array\merge_diff(
            [
                'a',
                'b',
                'c' => 'd',
                'e' => 'f',
            ],
            [
                'a',
                'c',
                'c' => 'd',
                'g' => 'h',
            ],
        );
        $result = [
            'b',
            'e' => 'f',
            'c',
            'g' => 'h',
        ];
        $this->assertEquals($test, $result);
    }
}
