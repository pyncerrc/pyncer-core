<?php
namespace Pyncer\Tests\Core;

use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testCleanUrl(): void
    {
        $test = 'https://www.example.com/test/?query=value';
        $test = \Pyncer\Http\clean_url($test);
        $result = 'https://www.example.com/test?query=value';
        $this->assertEquals($test, $result);

        $test = 'https://www.example.com?query=value';
        $test = \Pyncer\Http\clean_url($test);
        $result = 'https://www.example.com/?query=value';
        $this->assertEquals($test, $result);
    }

    public function testCleanPath(): void
    {
        $test = 'https://www.example.com/test/test/';
        $test = \Pyncer\Http\clean_path($test);
        $cleanPath = 'https://www.example.com/test/test';
        $this->assertEquals($test, $cleanPath);

        $test = 'test\\test/';
        $test = \Pyncer\Http\clean_path($test);
        $result = '/test/test';
        $this->assertEquals($test, $result);
    }

    public function testLtrimPath(): void
    {
        $test = \Pyncer\Http\ltrim_path(
            '/a/test/test/',
            'a/test',
        );
        $result = '/test';
        $this->assertEquals($test, $result);
    }

    public function testRtrimPath(): void
    {
        $test = \Pyncer\Http\rtrim_path(
            '/a/test/test/',
            'test',
        );
        $result = '/a/test';
        $this->assertEquals($test, $result);
    }

    public function testParseUrlQuery(): void
    {
        $test = \Pyncer\Http\parse_url_query(
            '?test=1&test=2',
        );
        $result = [
            'test' => '2',
        ];
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\parse_url_query(
            'test[]=1&test[]=2',
        );
        $result = [
            'test' => ['1', '2'],
        ];
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\parse_url_query(
            'test=1&foo=bar',
        );
        $result = [
            'test' => '1',
            'foo' => 'bar',
        ];
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\parse_url_query(
            'test[foo]=1&test[bar]=2',
        );
        $result = [
            'test' => [
                'foo' => '1',
                'bar' => '2',
            ],
        ];
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\parse_url_query(
            'test[][foo]=1&test[][bar]=2',
        );
        $result = [
            'test' => [
                ['foo' => '1'],
                ['bar' => '2'],
            ],
        ];
        $this->assertEquals($test, $result);
    }

    public function testMergeUrlQuery(): void
    {
        $test = \Pyncer\Http\merge_url_queries(
            'test[foo]=1&test[bar]=2',
            'test[foo]=3',
            [
                'test2' => 'yes'
            ],
            '0[1]=1&0[2]=1',
            '0[2]=3',
        );
        $result = [
            'test' => [
                'foo' => '3',
                'bar' => '2',
            ],
            'test2' => 'yes',
            '0' => [
                '1' => '1',
                '2' => '3',
            ],
        ];
        $this->assertEquals($test, $result);
    }

    public function testBuildUrlQuery(): void
    {
        $test = \Pyncer\Http\build_url_query([
            'test' => [
                'foo' => 3,
                'bar' => 2,
            ],
            'test2' => 'yes yes',
            'test3' => '',
            0 => [
                '1' => true,
                '2' => false,
                3 => null,
            ],
        ]);
        $result = 'test%5Bfoo%5D=3&test%5Bbar%5D=2&test2=yes%20yes&test3=&1=1&2=0&3';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\build_url_query([
            'test' => [
                ['foo' => '1'],
                ['bar' => '2'],
            ],
        ]);
        $result = 'test%5B0%5D%5Bfoo%5D=1&test%5B1%5D%5Bbar%5D=2';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\build_url_query([
            'test' => [
                ['1', '2'],
            ],
        ]);
        $result = 'test%5B0%5D%5B0%5D=1&test%5B0%5D%5B1%5D=2';
        $this->assertEquals($test, $result);
    }

    public function testRelativeUrl(): void
    {
        $test = \Pyncer\Http\relative_url(
            'https://pyncer.com/core',
            'https://pyncer.com',
        );
        $result = '/core';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\relative_url(
            'https://pyncer.com/core',
            'https://pyncer.com/',
        );
        $result = '/core';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\relative_url(
            'https://pyncer.com/core',
            'https://pyncer.org',
        );
        $result = 'https://pyncer.com/core';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\relative_url(
            'https://pyncer.com/core',
        );
        $result = '/core';
        $this->assertEquals($test, $result);
    }

    public function testAbsoluteUrl(): void
    {
        $test = \Pyncer\Http\absolute_url(
            '/core',
            'https://pyncer.com',
        );
        $result = 'https://pyncer.com/core';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\absolute_url(
            'core',
            'https://pyncer.com/',
        );
        $result = 'https://pyncer.com/core';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\absolute_url(
            'core',
            'https://pyncer.com',
        );
        $result = 'https://pyncer.com/core';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\absolute_url(
            'https://pyncer.com/core',
            'https://pyncer.org',
        );
        $result = 'https://pyncer.com/core';
        $this->assertEquals($test, $result);
    }

    public function testUrlEquals(): void
    {
        $test = \Pyncer\Http\url_equals(
            'https://pyncer.com',
            'https://pyncer.org',
        );
        $this->assertFalse($test);

        $test = \Pyncer\Http\url_equals(
            'https://pyncer.com/',
            'https://pyncer.com',
        );
        $this->assertTrue($test);

        $test = \Pyncer\Http\url_equals(
            'https://pyncer.com',
            'https://pyncer.com/',
        );
        $this->assertTrue($test);

        $test = \Pyncer\Http\url_equals(
            'https://pyncer.com/core?foo=bar&bar=foo',
            'https://pyncer.com/core?bar=foo&foo=bar',
        );
        $this->assertTrue($test);

        $test = \Pyncer\Http\url_equals(
            'https://pyncer.com/core?foo[bar]=1&foo[baz]=2',
            'https://pyncer.com/core?foo[baz]=2&foo[bar]=1',
        );
        $this->assertTrue($test);
    }

    public function testEncodeUrl(): void
    {
        $test = \Pyncer\Http\encode_url(
            'this is a test',
        );
        $result = 'this%20is%20a%20test';

        $this->assertEquals($test, $result);
        $test = \Pyncer\Http\encode_url(
            'this is a test',
            PHP_QUERY_RFC3986,
        );
        $result = 'this%20is%20a%20test';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\encode_url(
            'this is a test',
            PHP_QUERY_RFC1738,
        );
        $result = 'this+is+a+test';
    }

    public function testDecodeUrl(): void
    {
        $test = \Pyncer\Http\decode_url(
            'this%20is%20a%20test',
        );
        $result = 'this is a test';

        $test = \Pyncer\Http\decode_url(
            'this%20is%20a%20test',
            PHP_QUERY_RFC3986,
        );
        $result = 'this is a test';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\decode_url(
            'this+is+a+test',
            PHP_QUERY_RFC1738,
        );
        $result = 'this is a test';
        $this->assertEquals($test, $result);

        $test = \Pyncer\Http\decode_url(
            'this+is+a+test',
            PHP_QUERY_RFC3986,
        );
        $result = 'this+is+a+test';
        $this->assertEquals($test, $result);
    }

    public function testEncodeUrlPath(): void
    {
        $test = \Pyncer\Http\encode_url_path(
            '/test <wow>/#^/',
            PHP_QUERY_RFC3986,
        );
        $result = '/test%20%3Cwow%3E/%23%5E/';
        $this->assertEquals($test, $result);
    }

    public function testEncodeUrlUserInfo(): void
    {
        $test = \Pyncer\Http\encode_url_path(
            'user<name>:pass!@#$%^&*()[]<>',
            PHP_QUERY_RFC3986,
        );
        $result = 'user%3Cname%3E:pass!@%23$%25%5E&*()%5B%5D%3C%3E';
        $this->assertEquals($test, $result);
    }

    public function testEncodeUrlQuery(): void
    {
        $test = \Pyncer\Http\encode_url_path(
            '?!@#$%=^&*()[]<>&,.=bar',
            PHP_QUERY_RFC3986,
        );
        $result = '%3F!@%23$%25=%5E&*()%5B%5D%3C%3E&,.=bar';
        $this->assertEquals($test, $result);
    }

    public function testEncodeUrlFragment(): void
    {
        $test = \Pyncer\Http\encode_url_path(
            '#!@#$%=^&*()[]<>&,.=bar',
            PHP_QUERY_RFC3986,
        );
        $result = '%23!@%23$%25=%5E&*()%5B%5D%3C%3E&,.=bar';
        $this->assertEquals($test, $result);
    }

    public function testBase64Encode(): void
    {
        $test = \Pyncer\Http\base64_encode(
            '1234567890abcdefghijklmnopqrstuvwxyz',
        );
        $result = 'MTIzNDU2Nzg5MGFiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6';
        $this->assertEquals($test, $result);
    }

    public function testBase64Decode(): void
    {
        $test = \Pyncer\Http\base64_decode(
            'MTIzNDU2Nzg5MGFiY2RlZmdoaWprbG1ub3BxcnN0dXZ3eHl6',
        );
        $result = '1234567890abcdefghijklmnopqrstuvwxyz';
        $this->assertEquals($test, $result);
    }
}
