<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\AbstractCollection;
use Rak200\Collections\OrderedSet;

final class OrderedSetTest extends TestCase {

    public function testEmptyState(): void {
        $s = OrderedSet::any();
        self::assertCount(0, $s);
        self::assertNull($s->first());
        self::assertNull($s->last());
        self::assertSame([], $s->toArray());
    }

    public function testInsertionOrderByDefault(): void {
        $s = OrderedSet::any();
        $a = new \stdClass();
        $b = new \stdClass();
        $c = new \stdClass();
        $s->add($a);
        $s->add($b);
        $s->add($c);
        self::assertSame([$a, $b, $c], $s->toArray());
        self::assertSame($a, $s->first());
        self::assertSame($c, $s->last());
    }

    public function testAddReturnsBool(): void {
        $s = OrderedSet::any();
        $a = new \stdClass();
        self::assertTrue($s->add($a));
        self::assertFalse($s->add($a));
    }

    public function testRemoveAndContains(): void {
        $s = OrderedSet::any();
        $a = new \stdClass();
        self::assertFalse($s->contains($a));
        $s->add($a);
        self::assertTrue($s->contains($a));
        self::assertTrue($s->remove($a));
        self::assertFalse($s->contains($a));
        self::assertFalse($s->remove($a));
    }

    public function testCustomComparatorReordersOnAdd(): void {
        $byN = static fn(\stdClass $a, \stdClass $b): int => $a->n <=> $b->n;
        $s = OrderedSet::of(\stdClass::class, comparator: $byN);

        $three = new \stdClass(); $three->n = 3;
        $one   = new \stdClass(); $one->n   = 1;
        $two   = new \stdClass(); $two->n   = 2;

        $s->add($three);
        $s->add($one);
        $s->add($two);

        self::assertSame($one, $s->first());
        self::assertSame($three, $s->last());

        $ordered = [];
        foreach ($s as $obj) {
            $ordered[] = $obj->n;
        }
        self::assertSame([1, 2, 3], $ordered);
    }

    public function testTypeEnforcement(): void {
        $s = OrderedSet::of(\DateTimeImmutable::class);
        $this->expectException(InvalidArgumentException::class);
        $s->add(new \stdClass()); // @phpstan-ignore argument.type (runtime rejection test)
    }

    public function testInitialItemsDeduplicated(): void {
        $a = new \stdClass();
        $s = OrderedSet::any([$a, $a, $a]);
        self::assertCount(1, $s);
    }

    public function testInitialItemsWithComparator(): void {
        $byN = static fn(\stdClass $a, \stdClass $b): int => $a->n <=> $b->n;
        $three = new \stdClass(); $three->n = 3;
        $one   = new \stdClass(); $one->n   = 1;
        $two   = new \stdClass(); $two->n   = 2;
        $s = OrderedSet::of(\stdClass::class, [$three, $one, $two], $byN);
        $out = array_map(static fn(\stdClass $o): int => $o->n, $s->toArray());
        self::assertSame([1, 2, 3], $out);
    }

    public function testToArrayIsZeroIndexed(): void {
        $s = OrderedSet::any();
        $a = new \stdClass();
        $b = new \stdClass();
        $s->add($a);
        $s->add($b);
        self::assertSame([0, 1], array_keys($s->toArray()));
    }

    public function testIsAbstractCollection(): void {
        self::assertInstanceOf(AbstractCollection::class, OrderedSet::any());
    }

    public function testScalarsWithComparator(): void {
        $s = OrderedSet::any(comparator: static fn($a, $b) => $a <=> $b);
        $s->add(3);
        $s->add(1);
        $s->add(2);
        self::assertFalse($s->add(2));  // duplicate
        self::assertSame([1, 2, 3], $s->toArray());
    }

    public function testMixedInsertionOrder(): void {
        $s = OrderedSet::any();
        $obj = new \stdClass();
        $s->add('first');
        $s->add($obj);
        $s->add(42);
        self::assertSame(['first', $obj, 42], $s->toArray());
    }

    public function testIsEmptyAndClear(): void {
        $s = OrderedSet::any([1, 2, 3]);
        self::assertFalse($s->isEmpty());
        $s->clear();
        self::assertTrue($s->isEmpty());
        self::assertCount(0, $s);
    }

    public function testUnionPreservesComparator(): void {
        $asc = static fn($a, $b) => $a <=> $b;
        $a = OrderedSet::any([3, 1], $asc);
        $b = OrderedSet::any([2, 5]);
        self::assertSame([1, 2, 3, 5], $a->union($b)->toArray());
    }

    public function testIntersection(): void {
        $a = OrderedSet::any([1, 2, 3, 4]);
        $b = OrderedSet::any([3, 4, 5]);
        self::assertSame([3, 4], $a->intersection($b)->toArray());
    }

    public function testDifference(): void {
        $a = OrderedSet::any([1, 2, 3, 4]);
        $b = OrderedSet::any([3, 4, 5]);
        self::assertSame([1, 2], $a->difference($b)->toArray());
    }

    public function testIsSubsetOfAndIsSupersetOf(): void {
        $small = OrderedSet::any([1, 2]);
        $large = OrderedSet::any([1, 2, 3]);
        self::assertTrue($small->isSubsetOf($large));
        self::assertFalse($large->isSubsetOf($small));
        self::assertTrue($large->isSupersetOf($small));
    }
}
