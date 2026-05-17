<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\AbstractCollection;
use Rak200\Collections\Set;

final class SetTest extends TestCase {

    public function testEmptySetState(): void {
        $s = new Set();
        self::assertCount(0, $s);
        self::assertSame([], $s->toArray());
    }

    public function testAddReturnsTrueForNew(): void {
        $s = new Set();
        $obj = new \stdClass();
        self::assertTrue($s->add($obj));
        self::assertCount(1, $s);
    }

    public function testAddReturnsFalseForDuplicateInstance(): void {
        $s = new Set();
        $obj = new \stdClass();
        $s->add($obj);
        self::assertFalse($s->add($obj));
        self::assertCount(1, $s);
    }

    public function testDifferentInstancesAreDistinct(): void {
        $s = new Set();
        $a = new \stdClass();
        $b = new \stdClass();
        self::assertTrue($s->add($a));
        self::assertTrue($s->add($b));
        self::assertCount(2, $s);
    }

    public function testRemoveReturnsBool(): void {
        $s = new Set();
        $obj = new \stdClass();
        self::assertFalse($s->remove($obj));
        $s->add($obj);
        self::assertTrue($s->remove($obj));
        self::assertCount(0, $s);
    }

    public function testContains(): void {
        $s = new Set();
        $obj = new \stdClass();
        self::assertFalse($s->contains($obj));
        $s->add($obj);
        self::assertTrue($s->contains($obj));
    }

    public function testTypeEnforcement(): void {
        $s = new Set(\DateTimeImmutable::class);
        $this->expectException(InvalidArgumentException::class);
        $s->add(new \stdClass());
    }

    public function testToArrayIsZeroIndexed(): void {
        $s = new Set();
        $a = new \stdClass();
        $b = new \stdClass();
        $s->add($a);
        $s->add($b);
        $arr = $s->toArray();
        self::assertSame([0, 1], array_keys($arr));
        self::assertSame([$a, $b], $arr);
    }

    public function testInitialItemsDeduplicated(): void {
        $a = new \stdClass();
        $s = new Set('mixed', [$a, $a, $a]);
        self::assertCount(1, $s);
    }

    public function testIteration(): void {
        $a = new \stdClass();
        $b = new \stdClass();
        $s = new Set('mixed', [$a, $b]);
        $out = [];
        foreach ($s as $item) {
            $out[] = $item;
        }
        self::assertSame([$a, $b], $out);
    }

    public function testIsAbstractCollection(): void {
        self::assertInstanceOf(AbstractCollection::class, new Set());
    }
}
