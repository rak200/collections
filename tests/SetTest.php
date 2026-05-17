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

    public function testScalarsAreUniqueByValue(): void {
        $s = new Set('mixed');
        self::assertTrue($s->add('foo'));
        self::assertFalse($s->add('foo'));   // same string value
        self::assertTrue($s->add(42));
        self::assertFalse($s->add(42));      // same int value
        self::assertTrue($s->add('42'));     // distinct from int 42 thanks to type prefix
        self::assertCount(3, $s);
    }

    public function testNullAndBoolsAreUnique(): void {
        $s = new Set('mixed');
        self::assertTrue($s->add(null));
        self::assertFalse($s->add(null));
        self::assertTrue($s->add(true));
        self::assertTrue($s->add(false));
        self::assertCount(3, $s);
    }

    public function testMixedCollectionWithObjectsAndScalars(): void {
        $s = new Set('mixed');
        $obj = new \stdClass();
        $s->add($obj);
        $s->add('foo');
        $s->add(1);
        self::assertTrue($s->contains($obj));
        self::assertTrue($s->contains('foo'));
        self::assertTrue($s->contains(1));
        self::assertCount(3, $s);
    }

    public function testIsEmptyAndClear(): void {
        $s = new Set('mixed', [1, 2, 3]);
        self::assertFalse($s->isEmpty());
        $s->clear();
        self::assertTrue($s->isEmpty());
        self::assertCount(0, $s);
    }

    public function testUnion(): void {
        $a = new Set('mixed', [1, 2, 3]);
        $b = new Set('mixed', [3, 4, 5]);
        $u = $a->union($b);
        self::assertEqualsCanonicalizing([1, 2, 3, 4, 5], $u->toArray());
        // originals untouched
        self::assertCount(3, $a);
        self::assertCount(3, $b);
    }

    public function testIntersection(): void {
        $a = new Set('mixed', [1, 2, 3, 4]);
        $b = new Set('mixed', [3, 4, 5, 6]);
        self::assertEqualsCanonicalizing([3, 4], $a->intersection($b)->toArray());
    }

    public function testDifference(): void {
        $a = new Set('mixed', [1, 2, 3, 4]);
        $b = new Set('mixed', [3, 4, 5, 6]);
        self::assertEqualsCanonicalizing([1, 2], $a->difference($b)->toArray());
    }

    /**
     * A6: HashesValues hashes arrays via md5(serialize($value)). When the
     * array contains a Closure, serialize() throws a generic Exception, not
     * the InvalidArgumentException promised by the docblock. If this test
     * passes, A6 is fixed (wrapping serialize errors as InvalidArgumentException).
     */
    public function testAddArrayContainingClosureThrowsInvalidArgument(): void {
        $s = new Set();
        $this->expectException(InvalidArgumentException::class);
        $s->add([fn() => 1]);
    }

    public function testIsSubsetOfAndIsSupersetOf(): void {
        $small = new Set('mixed', [1, 2]);
        $large = new Set('mixed', [1, 2, 3]);
        self::assertTrue($small->isSubsetOf($large));
        self::assertFalse($large->isSubsetOf($small));
        self::assertTrue($large->isSupersetOf($small));
        self::assertFalse($small->isSupersetOf($large));
        // empty set is a subset of every set
        self::assertTrue((new Set())->isSubsetOf($large));
    }
}
