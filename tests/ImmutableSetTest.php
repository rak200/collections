<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\ImmutableSet;
use Rak200\Collections\Set;
use ReflectionClass;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class ImmutableSetTest extends TestCase
{
    public function testEmptyState(): void
    {
        $s = ImmutableSet::any();
        self::assertCount(0, $s);
        self::assertTrue($s->isEmpty());
        self::assertSame([], $s->toArray());
        self::assertFalse($s->contains('anything'));
        self::assertSame('mixed', $s->getType());
    }

    public function testInitialItemsDeduplicated(): void
    {
        $s = ImmutableSet::any(['a', 'b', 'a', 'c', 'b']);
        self::assertCount(3, $s);
        self::assertSame(['a', 'b', 'c'], $s->toArray());
    }

    public function testContains(): void
    {
        $s = ImmutableSet::any(['a', 'b']);
        self::assertTrue($s->contains('a'));
        self::assertFalse($s->contains('c'));
    }

    public function testTypeEnforcement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ImmutableSet::ofInt(['not-int']);
    }

    public function testFromSetPreservesType(): void
    {
        $set = Set::of(stdClass::class);
        $obj = new stdClass();
        $set->add($obj);
        $imm = ImmutableSet::fromSet($set);
        self::assertSame(stdClass::class, $imm->getType());
        self::assertTrue($imm->contains($obj));
    }

    public function testUnionWithImmutable(): void
    {
        $a = ImmutableSet::any([1, 2]);
        $b = ImmutableSet::any([2, 3]);
        $u = $a->union($b);
        self::assertInstanceOf(ImmutableSet::class, $u);
        self::assertSame([1, 2, 3], $u->toArray());
    }

    public function testUnionWithMutableSet(): void
    {
        $a = ImmutableSet::any([1]);
        $b = Set::any([2]);
        self::assertSame([1, 2], $a->union($b)->toArray());
    }

    public function testUnionIncludesAllItemsFromBothSidesNotJustTheFirst(): void
    {
        $a = ImmutableSet::any([1, 2, 3]);
        $b = ImmutableSet::any([4]);
        self::assertSame([1, 2, 3, 4], $a->union($b)->toArray());
    }

    public function testRewindResetsCursorAfterAdvancing(): void
    {
        $s = ImmutableSet::any([1, 2, 3]);
        $s->rewind();
        $s->next();
        self::assertSame(2, $s->current());
        $s->rewind();
        self::assertSame(1, $s->current());
    }

    public function testIntersection(): void
    {
        $a = ImmutableSet::any([1, 2, 3]);
        $b = ImmutableSet::any([2, 3, 4]);
        self::assertSame([2, 3], $a->intersection($b)->toArray());
    }

    public function testDifference(): void
    {
        $a = ImmutableSet::any([1, 2, 3]);
        $b = ImmutableSet::any([2]);
        self::assertSame([1, 3], $a->difference($b)->toArray());
    }

    public function testSubsetAndSuperset(): void
    {
        $a = ImmutableSet::any([1, 2]);
        $b = ImmutableSet::any([1, 2, 3]);
        self::assertTrue($a->isSubsetOf($b));
        self::assertFalse($b->isSubsetOf($a));
        self::assertTrue($b->isSupersetOf($a));
        self::assertFalse($a->isSupersetOf($b));
    }

    public function testIterationKeyIsSequential(): void
    {
        $s = ImmutableSet::any(['a', 'b', 'c']);
        $out = [];
        foreach ($s as $k => $v) {
            $out[$k] = $v;
        }
        self::assertSame([0 => 'a', 1 => 'b', 2 => 'c'], $out);
    }

    public function testNoMutationApiExists(): void
    {
        $reflect = new ReflectionClass(ImmutableSet::class);
        self::assertFalse($reflect->hasMethod('add'));
        self::assertFalse($reflect->hasMethod('remove'));
        self::assertFalse($reflect->hasMethod('clear'));
    }

    public function testMixedAcceptsScalarsAndNull(): void
    {
        $s = ImmutableSet::any([1, 'one', null, 1, 'one']);
        self::assertCount(3, $s);
        self::assertTrue($s->contains(null));
    }
}
