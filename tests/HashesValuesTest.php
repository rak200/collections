<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\Set;
use ReflectionMethod;
use stdClass;

use function fopen;
use function md5;
use function serialize;
use function spl_object_id;
use function var_export;

/**
 * Pins the exact string format `Internal\HashesValues::hashValue()` produces
 * per type — {@see Set} is used as the reflection target since the trait's
 * `private static` method has no direct public surface. The prefix and its
 * position matter (it is what keeps `1` and `'1'` from colliding), so each
 * case asserts the literal output rather than just "different inputs hash
 * differently".
 *
 * @internal
 *
 * @coversNothing
 */
final class HashesValuesTest extends TestCase
{
    public function testObjectHash(): void
    {
        $obj = new stdClass();
        self::assertSame('o:' . spl_object_id($obj), self::hash($obj));
    }

    public function testIntHash(): void
    {
        self::assertSame('i:42', self::hash(42));
    }

    public function testStringHash(): void
    {
        self::assertSame('s:foo', self::hash('foo'));
    }

    public function testBoolHash(): void
    {
        self::assertSame('b:1', self::hash(true));
        self::assertSame('b:0', self::hash(false));
    }

    public function testNullHash(): void
    {
        self::assertSame('n:', self::hash(null));
    }

    public function testFloatHash(): void
    {
        self::assertSame('f:' . var_export(1.5, true), self::hash(1.5));
    }

    public function testArrayHash(): void
    {
        self::assertSame('a:' . md5(serialize([1, 2])), self::hash([1, 2]));
    }

    public function testUnsupportedTypeThrows(): void
    {
        $resource = fopen('php://memory', 'r');
        self::assertNotFalse($resource);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported value type: resource');
        self::hash($resource);
    }

    public function testUnserializableArrayThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot hash array: Serialization of 'Closure' is not allowed");
        self::hash(['fn' => static function (): int {
            return 1;
        }]);
    }

    private static function hash(mixed $value): string
    {
        /** @var string $hash hashValue()'s return type is declared string; Reflection erases it to mixed */
        $hash = (new ReflectionMethod(Set::class, 'hashValue'))->invoke(null, $value);

        return $hash;
    }
}
