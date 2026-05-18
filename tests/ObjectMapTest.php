<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\ObjectMap;

final class ObjectMapTest extends TestCase {

    public function testEmptyState(): void {
        $m = new ObjectMap();
        self::assertCount(0, $m);
        self::assertTrue($m->isEmpty());
        self::assertSame([], $m->toArray());
        self::assertSame([], $m->keys());
        self::assertSame([], $m->values());
    }

    public function testDefaultTypesAreObject(): void {
        $m = new ObjectMap();
        self::assertSame('object', $m->getKeyType());
        self::assertSame('object', $m->getValueType());
    }

    public function testSetGetHas(): void {
        $m = new ObjectMap();
        $key = new \stdClass();
        $value = new \stdClass();
        $m->set($key, $value);
        self::assertSame($value, $m->get($key));
        self::assertTrue($m->has($key));
        self::assertFalse($m->has(new \stdClass()));
        self::assertNull($m->get(new \stdClass()));
    }

    public function testIdentityByInstance(): void {
        // Two distinct instances are different keys even if structurally equal.
        $m = new ObjectMap();
        $k1 = new \stdClass();
        $k2 = new \stdClass();
        $v1 = new \stdClass();
        $v2 = new \stdClass();
        $m->set($k1, $v1);
        $m->set($k2, $v2);
        self::assertCount(2, $m);
        self::assertSame($v1, $m->get($k1));
        self::assertSame($v2, $m->get($k2));
    }

    public function testSetSameKeyOverwrites(): void {
        $m = new ObjectMap();
        $key = new \stdClass();
        $v1 = new \stdClass();
        $v2 = new \stdClass();
        $m->set($key, $v1);
        $m->set($key, $v2);
        self::assertCount(1, $m);
        self::assertSame($v2, $m->get($key));
    }

    public function testRemoveReturnsBool(): void {
        $m = new ObjectMap();
        $key = new \stdClass();
        $m->set($key, new \stdClass());
        self::assertTrue($m->remove($key));
        self::assertFalse($m->remove($key));
        self::assertCount(0, $m);
    }

    public function testGetMissingKeyReturnsNull(): void {
        $m = new ObjectMap();
        self::assertNull($m->get(new \stdClass()));
    }

    public function testKeyTypeEnforcement(): void {
        $m = new ObjectMap(\DateTimeImmutable::class);
        $this->expectException(InvalidArgumentException::class);
        $m->set(new \stdClass(), new \stdClass());
    }

    public function testKeyTypeAcceptsCorrectInstance(): void {
        $m = new ObjectMap(\DateTimeImmutable::class);
        $key = new \DateTimeImmutable();
        $m->set($key, new \stdClass());
        self::assertTrue($m->has($key));
    }

    public function testValueTypeEnforcement(): void {
        $m = new ObjectMap('object', \DateTimeImmutable::class);
        $this->expectException(InvalidArgumentException::class);
        $m->set(new \stdClass(), new \stdClass());
    }

    public function testValueTypeAcceptsCorrectInstance(): void {
        $m = new ObjectMap('object', \DateTimeImmutable::class);
        $key = new \stdClass();
        $value = new \DateTimeImmutable();
        $m->set($key, $value);
        self::assertSame($value, $m->get($key));
    }

    public function testKeysAndValuesPreserveInsertionOrder(): void {
        $m = new ObjectMap();
        $k1 = new \stdClass();
        $k2 = new \stdClass();
        $k3 = new \stdClass();
        $v1 = new \stdClass();
        $v2 = new \stdClass();
        $v3 = new \stdClass();
        $m->set($k1, $v1);
        $m->set($k2, $v2);
        $m->set($k3, $v3);
        self::assertSame([$k1, $k2, $k3], $m->keys());
        self::assertSame([$v1, $v2, $v3], $m->values());
    }

    public function testIterationYieldsObjectKeysInOrder(): void {
        $m = new ObjectMap();
        $k1 = new \stdClass();
        $k2 = new \stdClass();
        $v1 = new \stdClass();
        $v2 = new \stdClass();
        $m->set($k1, $v1);
        $m->set($k2, $v2);

        $pairs = [];
        foreach ($m as $k => $v) {
            $pairs[] = [$k, $v];
        }
        self::assertSame([[$k1, $v1], [$k2, $v2]], $pairs);
    }

    public function testInitialPairs(): void {
        $k1 = new \stdClass();
        $k2 = new \stdClass();
        $v1 = new \stdClass();
        $v2 = new \stdClass();
        $m = new ObjectMap('object', 'object', [[$k1, $v1], [$k2, $v2]]);
        self::assertCount(2, $m);
        self::assertSame($v1, $m->get($k1));
        self::assertSame($v2, $m->get($k2));
        self::assertSame([$k1, $k2], $m->keys());
    }

    public function testInitialPairsValidated(): void {
        $this->expectException(InvalidArgumentException::class);
        new ObjectMap(\DateTimeImmutable::class, 'object', [
            [new \stdClass(), new \stdClass()],
        ]);
    }

    public function testToArrayReturnsListOfPairs(): void {
        $m = new ObjectMap();
        $k1 = new \stdClass();
        $k2 = new \stdClass();
        $v1 = new \stdClass();
        $v2 = new \stdClass();
        $m->set($k1, $v1);
        $m->set($k2, $v2);
        self::assertSame([[$k1, $v1], [$k2, $v2]], $m->toArray());
    }

    public function testIsEmptyAndClear(): void {
        $m = new ObjectMap();
        self::assertTrue($m->isEmpty());
        $m->set(new \stdClass(), new \stdClass());
        $m->set(new \stdClass(), new \stdClass());
        self::assertFalse($m->isEmpty());
        $m->clear();
        self::assertTrue($m->isEmpty());
        self::assertCount(0, $m);
        self::assertSame([], $m->toArray());
    }

    public function testDoesNotImplementArrayAccess(): void {
        $m = new ObjectMap();
        self::assertNotInstanceOf(\ArrayAccess::class, $m);
    }

    public function testImplementsExpectedInterfaces(): void {
        $m = new ObjectMap();
        self::assertInstanceOf(\Iterator::class, $m);
        self::assertInstanceOf(\Countable::class, $m);
        self::assertInstanceOf(ToArray::class, $m);
    }

    public function testScalarKeyRejectedByTypeHint(): void {
        $m = new ObjectMap();
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        $m->set('not-object', new \stdClass());
    }

    public function testScalarValueRejectedByTypeHint(): void {
        $m = new ObjectMap();
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        $m->set(new \stdClass(), 'not-object');
    }
}
