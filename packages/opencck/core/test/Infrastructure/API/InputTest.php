<?php
namespace OpenCCK\Infrastructure\API;

use OpenCCK\Infrastructure\API\Input\Filter;
use PHPUnit\Framework\TestCase;

/**
 * Class InputTest
 * @package API
 */
class InputTest extends TestCase {
    /**
     * @dataProvider providerBoolean
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testBoolean($value, $type, $expected) {
        $this->assertSame($expected, (new Input(['value' => $value]))->get('value', null, $type));
    }

    public function providerBoolean() {
        return [
            'boolean to boolean' => [true, Filter::BOOLEAN, true],
            'integer(0) to boolean' => [0, 'boolean', false],
            'integer(1) to boolean' => [1, 'boolean', true],
            'string(1) to boolean' => ['1', 'boolean', true],
            'string(2) to boolean' => ['0', 'boolean', false],
            'string(true) to boolean' => ['true', 'boolean', true],
            'string(false) to boolean' => ['false', 'boolean', false],

            'integer(2) to boolean' => [2, 'boolean', null],
            'float to boolean' => [1.5, 'boolean', null],
            'string(abc) to boolean' => ['abc', 'boolean', null],
            'email to boolean' => ['test@test.com', 'boolean', null],
            'url to boolean' => ['https://test.com', 'boolean', null],
            'ip to boolean' => ['1.1.1.1', 'boolean', null],
            'raw to boolean' => ['<abc>test</abc>', 'boolean', null],
            'array to boolean' => [['1'], 'boolean', null],
            'object to boolean' => [(object) ['1'], 'boolean', null],
        ];
    }

    /**
     * @dataProvider providerInteger
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testInteger($value, $type, $expected) {
        $this->assertSame($expected, (new Input(['value' => $value]))->get('value', null, $type));
    }

    public function providerInteger() {
        return [
            'boolean(true) to integer' => [true, Filter::INTEGER, 1],
            'boolean(false) to integer' => [false, 'integer', 0],
            'integer to integer' => [2, 'integer', 2],
            'string to integer' => ['123', 'integer', 123],

            'float to integer' => [2.56, 'integer', null],
            'email to integer' => ['test@test.com', 'integer', null],
            'url to integer' => ['https://test.com', 'integer', null],
            'ip to integer' => ['1.1.1.1', 'integer', null],
            'raw to integer' => ['<abc>test</abc>', 'integer', null],
            'array to integer' => [['1'], 'integer', null],
            'object to integer' => [(object) ['1'], 'integer', null],
        ];
    }

    /**
     * @dataProvider providerFloat
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testFloat($value, $type, $expected) {
        $this->assertSame($expected, (new Input(['value' => $value]))->get('value', null, $type));
    }

    public function providerFloat() {
        return [
            'integer to float' => [123, Filter::FLOAT, 123.0],
            'float to float' => [2.45, 'float', 2.45],
            'string(123) to float' => ['123', 'float', 123.0],
            'string(2.45) to float' => ['2.45', 'float', 2.45],

            'string(abc) to float' => ['abc', 'float', null],
            'boolean to float' => [false, 'float', null],
            'email to float' => ['test@test.com', 'float', null],
            'url to float' => ['https://test.com', 'float', null],
            'ip to float' => ['1.1.1.1', 'float', null],
            'raw to float' => ['<abc>test</abc>', 'float', null],
            'array to float' => [['1'], 'float', null],
            'object to float' => [(object) ['1'], 'float', null],
        ];
    }

    /**
     * @dataProvider providerString
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testString($value, $type, $expected) {
        $this->assertSame($expected, (new Input(['value' => $value]))->get('value', null, $type));
    }

    public function providerString() {
        return [
            'boolean(true) to string' => [true, Filter::STRING, '1'],
            'boolean(false) to string' => [false, 'string', ''],
            'integer to string' => [123, 'string', '123'],
            'float to string' => [2.45, 'string', '2.45'],
            'string to string' => ['abc', 'string', 'abc'],
            'email to string' => ['test@test.com', 'string', 'test@test.com'],
            'url to string' => ['https://test.com', 'string', 'https://test.com'],
            'ip to string' => ['1.1.1.1', 'string', '1.1.1.1'],
            'raw to string' => ['<abc>test</abc>', 'string', 'test'],

            'array to string' => [['1'], 'string', null],
            'object to string' => [(object) ['1'], 'string', null],
        ];
    }

    /**
     * @dataProvider providerEmail
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testEmail($value, $type, $expected) {
        $this->assertSame($expected, (new Input(['value' => $value]))->get('value', null, $type));
    }

    public function providerEmail() {
        return [
            'email to email' => ['test@test.com', Filter::EMAIL, 'test@test.com'],

            'boolean to email' => [true, 'email', null],
            'integer to email' => [123, 'email', null],
            'float to email' => [2.45, 'email', null],
            'string to email' => ['abc', 'email', null],
            'url to email' => ['https://test.com', 'email', null],
            'ip to email' => ['1.1.1.1', 'email', null],
            'raw to email' => ['<abc>test</abc>', 'email', null],
            'array to email' => [['1'], 'email', null],
            'object to email' => [(object) ['1'], 'email', null],
        ];
    }

    /**
     * @dataProvider providerUrl
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testUrl($value, $type, $expected) {
        $this->assertSame($expected, (new Input(['value' => $value]))->get('value', null, $type));
    }

    public function providerUrl() {
        return [
            'url(none path) to url' => ['https://test.com', Filter::URL, 'https://test.com'],
            'url(with path) to url' => ['https://test.com/abc', 'url', 'https://test.com/abc'],

            'boolean to url' => [false, 'url', null],
            'integer to url' => [123, 'url', null],
            'float to url' => [2.45, 'url', null],
            'string to url' => ['abc', 'url', null],
            'email to url' => ['test@test.com', 'url', null],
            'ip to url' => ['1.1.1.1', 'url', null],
            'raw to url' => ['<abc>test</abc>', 'url', null],
            'array to url' => [['1'], 'url', null],
            'object to url' => [(object) ['1'], 'url', null],
        ];
    }

    /**
     * @dataProvider providerIp
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testIp($value, $type, $expected) {
        $this->assertSame($expected, (new Input(['value' => $value]))->get('value', null, $type));
    }

    public function providerIp() {
        return [
            'ip to ip' => ['1.1.1.1', Filter::IP, '1.1.1.1'],

            'boolean to ip' => [false, 'ip', null],
            'integer to ip' => [123, 'ip', null],
            'float to ip' => [2.45, 'ip', null],
            'string to ip' => ['abc', 'ip', null],
            'email to ip' => ['test@test.com', 'ip', null],
            'url to ip' => ['https://test.com', 'ip', null],
            'raw to ip' => ['<abc>test</abc>', 'ip', null],
            'array to ip' => [['1'], 'ip', null],
            'object to ip' => [(object) ['1'], 'ip', null],
        ];
    }

    /**
     * @dataProvider providerRaw
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testRaw($value, $type, $expected) {
        $this->assertSame($expected, (new Input(['value' => $value]))->get('value', null, $type));
    }

    public function providerRaw() {
        $object = (object) ['1'];
        return [
            'boolean to raw' => [false, Filter::RAW, false],
            'integer to raw' => [123, 'raw', 123],
            'float to raw' => [2.45, 'raw', 2.45],
            'string to raw' => ['abc', 'raw', 'abc'],
            'email to raw' => ['test@test.com', 'raw', 'test@test.com'],
            'url to raw' => ['https://test.com', 'raw', 'https://test.com'],
            'ip to raw' => ['1.1.1.1', 'raw', '1.1.1.1'],
            'raw to raw' => ['<abc>test</abc>', 'raw', '<abc>test</abc>'],
            'array to raw' => [['1'], 'raw', ['1']],
            'object to raw' => [$object, 'raw', $object],
        ];
    }

    /**
     * @dataProvider providerArray
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testArray($value, $type, $expected) {
        $this->assertSame($expected, (new Input(['value' => $value]))->get('value', null, $type));
    }

    public function providerArray() {
        return [
            'boolean to array' => [true, Filter::ARRAY, [true]],
            'integer to array' => [123, 'array', [123]],
            'float to array' => [2.45, 'array', [2.45]],
            'string to array' => ['abc', 'array', ['abc']],
            'email to array' => ['test@test.com', 'array', ['test@test.com']],
            'url to array' => ['https://test.com', 'array', ['https://test.com']],
            'ip to array' => ['1.1.1.1', 'array', ['1.1.1.1']],
            'raw to array' => ['<abc>test</abc>', 'array', ['<abc>test</abc>']],
            'array to array' => [['1'], 'array', ['1']],
            'object to array' => [(object) ['1'], 'array', ['1']],
        ];
    }

    /**
     * @dataProvider providerObject
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testObject($value, $type, $expected) {
        $this->assertEquals($expected, (new Input(['value' => $value]))->get('value', null, $type));
    }

    public function providerObject() {
        return [
            'boolean to object' => [true, Filter::OBJECT, (object) ['scalar' => true]],
            'integer to object' => [123, 'object', (object) ['scalar' => 123]],
            'float to object' => [2.45, 'object', (object) ['scalar' => 2.45]],
            'string to object' => ['abc', 'object', (object) ['scalar' => 'abc']],
            'email to object' => ['test@test.com', 'object', (object) ['scalar' => 'test@test.com']],
            'url to object' => ['https://test.com', 'object', (object) ['scalar' => 'https://test.com']],
            'ip to object' => ['1.1.1.1', 'object', (object) ['scalar' => '1.1.1.1']],
            'raw to object' => ['<abc>test</abc>', 'object', (object) ['scalar' => '<abc>test</abc>']],
            'array to object' => [['1'], 'object', (object) ['1']],
            'object to object' => [(object) ['1'], 'object', (object) ['1']],
        ];
    }
}
