<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use ArrayAccess;
use Countable;
use DateTimeImmutable;
use InvalidArgumentException;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\ObjectMap;
use stdClass;
use TypeError;

/**
 * @internal
 *
 * @coversNothing
 */
final class ObjectMapTest extends TestCase
{
    use ConstructsProtected;

    public function testEmptyState(): void
    {
        $m = ObjectMap::any();
        self::assertCount(0, $m);
        self::assertTrue($m->isEmpty());
        self::assertSame([], $m->toArray());
        self::assertSame([], $m->keys());
        self::assertSame([], $m->values());
    }

    public function testDefaultTypesAreObject(): void
    {
        $m = ObjectMap::any();
        self::assertSame('object', $m->getKeyType());
        self::assertSame('object', $m->getValueType());
    }

    public function testSetGetHas(): void
    {
        $m = ObjectMap::any();
        $key = new stdClass();
        $value = new stdClass();
        $m->set($key, $value);
        self::assertSame($value, $m->get($key));
        self::assertTrue($m->has($key));
        self::assertFalse($m->has(new stdClass()));
        self::assertNull($m->get(new stdClass()));
    }

    public function testIdentityByInstance(): void
    {
        // Two distinct instances are different keys even if structurally equal.
        $m = ObjectMap::any();
        $k1 = new stdClass();
        $k2 = new stdClass();
        $v1 = new stdClass();
        $v2 = new stdClass();
        $m->set($k1, $v1);
        $m->set($k2, $v2);
        self::assertCount(2, $m);
        self::assertSame($v1, $m->get($k1));
        self::assertSame($v2, $m->get($k2));
    }

    public function testSetSameKeyOverwrites(): void
    {
        $m = ObjectMap::any();
        $key = new stdClass();
        $v1 = new stdClass();
        $v2 = new stdClass();
        $m->set($key, $v1);
        $m->set($key, $v2);
        self::assertCount(1, $m);
        self::assertSame($v2, $m->get($key));
    }

    public function testRemoveReturnsBool(): void
    {
        $m = ObjectMap::any();
        $key = new stdClass();
        $m->set($key, new stdClass());
        self::assertTrue($m->remove($key));
        self::assertFalse($m->remove($key));
        self::assertCount(0, $m);
    }

    public function testGetMissingKeyReturnsNull(): void
    {
        $m = ObjectMap::any();
        self::assertNull($m->get(new stdClass()));
    }

    #[DataProvider('rejectedPairProvider')]
    public function testKeyOrValueTypeEnforcement(string $keyType, string $valueType, object $key, object $value): void
    {
        $m = self::build(ObjectMap::class, $keyType, $valueType);
        $this->expectException(InvalidArgumentException::class);
        $m->set($key, $value);
    }

    /** @return iterable<string, array{string, string, object, object}> */
    public static function rejectedPairProvider(): iterable
    {
        yield 'stdClass key into DateTimeImmutable-keyed map' => [DateTimeImmutable::class, 'object', new stdClass(), new stdClass()];

        yield 'stdClass value into DateTimeImmutable-valued map' => ['object', DateTimeImmutable::class, new stdClass(), new stdClass()];
    }

    public function testKeyTypeAcceptsCorrectInstance(): void
    {
        $m = self::build(ObjectMap::class, DateTimeImmutable::class);
        $key = new DateTimeImmutable();
        $m->set($key, new stdClass());
        self::assertTrue($m->has($key));
    }

    public function testValueTypeAcceptsCorrectInstance(): void
    {
        $m = self::build(ObjectMap::class, 'object', DateTimeImmutable::class);
        $key = new stdClass();
        $value = new DateTimeImmutable();
        $m->set($key, $value);
        self::assertSame($value, $m->get($key));
    }

    public function testKeysAndValuesPreserveInsertionOrder(): void
    {
        $m = ObjectMap::any();
        $k1 = new stdClass();
        $k2 = new stdClass();
        $k3 = new stdClass();
        $v1 = new stdClass();
        $v2 = new stdClass();
        $v3 = new stdClass();
        $m->set($k1, $v1);
        $m->set($k2, $v2);
        $m->set($k3, $v3);
        self::assertSame([$k1, $k2, $k3], $m->keys());
        self::assertSame([$v1, $v2, $v3], $m->values());
    }

    public function testIterationYieldsObjectKeysInOrder(): void
    {
        $m = ObjectMap::any();
        $k1 = new stdClass();
        $k2 = new stdClass();
        $v1 = new stdClass();
        $v2 = new stdClass();
        $m->set($k1, $v1);
        $m->set($k2, $v2);

        $pairs = [];
        foreach ($m as $k => $v) {
            $pairs[] = [$k, $v];
        }
        self::assertSame([[$k1, $v1], [$k2, $v2]], $pairs);
    }

    public function testInitialPairs(): void
    {
        $k1 = new stdClass();
        $k2 = new stdClass();
        $v1 = new stdClass();
        $v2 = new stdClass();
        $m = ObjectMap::any([[$k1, $v1], [$k2, $v2]]);
        self::assertCount(2, $m);
        self::assertSame($v1, $m->get($k1));
        self::assertSame($v2, $m->get($k2));
        self::assertSame([$k1, $k2], $m->keys());
    }

    public function testInitialPairsValidated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        self::build(ObjectMap::class, DateTimeImmutable::class, 'object', [
            [new stdClass(), new stdClass()],
        ]);
    }

    public function testToArrayReturnsListOfPairs(): void
    {
        $m = ObjectMap::any();
        $k1 = new stdClass();
        $k2 = new stdClass();
        $v1 = new stdClass();
        $v2 = new stdClass();
        $m->set($k1, $v1);
        $m->set($k2, $v2);
        self::assertSame([[$k1, $v1], [$k2, $v2]], $m->toArray());
    }

    public function testRewindResetsCursorAfterAdvancing(): void
    {
        $m = ObjectMap::any();
        $k1 = new stdClass();
        $k2 = new stdClass();
        $v1 = new stdClass();
        $v2 = new stdClass();
        $m->set($k1, $v1);
        $m->set($k2, $v2);
        $m->rewind();
        $m->next();
        self::assertSame($v2, $m->current());
        $m->rewind();
        self::assertSame($v1, $m->current());
        self::assertSame($k1, $m->key());
    }

    public function testIsEmptyAndClear(): void
    {
        $m = ObjectMap::any();
        self::assertTrue($m->isEmpty());
        $m->set(new stdClass(), new stdClass());
        $m->set(new stdClass(), new stdClass());
        self::assertFalse($m->isEmpty());
        $m->clear();
        self::assertTrue($m->isEmpty());
        self::assertCount(0, $m);
        self::assertSame([], $m->toArray());
    }

    public function testDoesNotImplementArrayAccess(): void
    {
        $m = ObjectMap::any();
        self::assertNotInstanceOf(ArrayAccess::class, $m);
    }

    public function testImplementsExpectedInterfaces(): void
    {
        $m = ObjectMap::any();
        self::assertInstanceOf(Iterator::class, $m);
        self::assertInstanceOf(Countable::class, $m);
        self::assertInstanceOf(ToArray::class, $m);
    }

    #[DataProvider('scalarPairProvider')]
    public function testScalarRejectedByTypeHint(string $method, mixed $key, mixed $value): void
    {
        $m = ObjectMap::any();
        $this->expectException(TypeError::class);
        $m->{$method}($key, $value);
    }

    /** @return iterable<string, array{string, mixed, mixed}> */
    public static function scalarPairProvider(): iterable
    {
        yield 'scalar key' => ['set', 'not-object', new stdClass()];

        yield 'scalar value' => ['set', new stdClass(), 'not-object'];
    }
}
