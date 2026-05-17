<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\AbstractCollection;
use Rak200\Collections\Vector;

final class VectorTest extends TestCase {

    public function testEmptyConstruction(): void {
        $v = new Vector();
        self::assertSame('mixed', $v->getType());
        self::assertCount(0, $v);
        self::assertSame([], $v->toArray());
    }

    public function testConstructionWithMixedAcceptsScalarsAndObjects(): void {
        $v = new Vector('mixed', [1, 'two', 3.14, new \stdClass()]);
        self::assertCount(4, $v);
    }

    public function testConstructionWithClassTypeAcceptsInstances(): void {
        $a = new \stdClass();
        $b = new \stdClass();
        $v = new Vector(\stdClass::class, [$a, $b]);
        self::assertCount(2, $v);
        self::assertSame($a, $v->get(0));
    }

    public function testConstructionRejectsNonInstance(): void {
        $this->expectException(InvalidArgumentException::class);
        new Vector(\stdClass::class, ['not-an-object']);
    }

    public function testAddAndGet(): void {
        $v = new Vector();
        $v->add(0, 'a');
        $v->add(1, 'b');
        self::assertSame('a', $v->get(0));
        self::assertSame('b', $v->get(1));
    }

    public function testGetReturnsNullForMissingKey(): void {
        $v = new Vector();
        self::assertNull($v->get(99));
    }

    public function testRemove(): void {
        $v = new Vector('mixed', [0 => 'a', 1 => 'b']);
        $v->remove(0);
        self::assertNull($v->get(0));
        self::assertSame('b', $v->get(1));
        self::assertCount(1, $v);
    }

    public function testAddRejectsBadType(): void {
        $v = new Vector(\stdClass::class);
        $this->expectException(InvalidArgumentException::class);
        $v->add(0, 'not-an-object');
    }

    public function testArrayAccessOffsetSetWithExplicitOffset(): void {
        $v = new Vector();
        $v[5] = 'hello';
        self::assertSame('hello', $v[5]);
        self::assertTrue(isset($v[5]));
    }

    public function testArrayAccessOffsetSetWithNullAppends(): void {
        $v = new Vector();
        $v[] = 'a';
        $v[] = 'b';
        self::assertSame('a', $v[0]);
        self::assertSame('b', $v[1]);
    }

    public function testArrayAccessOffsetGetReturnsNullForMissing(): void {
        $v = new Vector();
        self::assertNull($v[42]);
    }

    public function testArrayAccessOffsetExistsAndUnset(): void {
        $v = new Vector('mixed', [0 => 'a', 1 => 'b']);
        self::assertTrue(isset($v[0]));
        unset($v[0]);
        self::assertFalse(isset($v[0]));
    }

    public function testArrayAccessOffsetSetRejectsBadType(): void {
        $v = new Vector(\stdClass::class);
        $this->expectException(InvalidArgumentException::class);
        $v[0] = 'scalar';
    }

    public function testIterationPreservesKeysAndOrder(): void {
        $v = new Vector('mixed', [10 => 'a', 20 => 'b', 30 => 'c']);
        $out = [];
        foreach ($v as $k => $val) {
            $out[$k] = $val;
        }
        self::assertSame([10 => 'a', 20 => 'b', 30 => 'c'], $out);
    }

    public function testToArrayReturnsUnderlyingArray(): void {
        $v = new Vector('mixed', [1, 2, 3]);
        self::assertSame([1, 2, 3], $v->toArray());
    }

    public function testIsAbstractCollection(): void {
        self::assertInstanceOf(AbstractCollection::class, new Vector());
        self::assertInstanceOf(\ArrayAccess::class, new Vector());
    }
}
