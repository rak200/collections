<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\MultiSet;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class MultiSetTest extends TestCase
{
    public function testEmptyState(): void
    {
        $b = MultiSet::any();
        self::assertCount(0, $b);
        self::assertSame(0, $b->distinct());
        self::assertTrue($b->isEmpty());
        self::assertSame([], $b->toArray());
        self::assertSame(0, $b->countOf('missing'));
        self::assertFalse($b->contains('missing'));
        self::assertSame('mixed', $b->getType());
    }

    public function testAddIncrementsCount(): void
    {
        $b = MultiSet::any();
        self::assertSame(1, $b->add('apple'));
        self::assertSame(2, $b->add('apple'));
        self::assertSame(5, $b->add('apple', 3));
        self::assertSame(5, $b->countOf('apple'));
        self::assertSame(1, $b->distinct());
        self::assertCount(5, $b);
    }

    public function testRemoveDecrementsAndDropsAtZero(): void
    {
        $b = MultiSet::any();
        $b->add('x', 3);
        self::assertSame(2, $b->remove('x'));
        self::assertSame(0, $b->remove('x', 5));
        self::assertFalse($b->contains('x'));
        self::assertSame(0, $b->remove('x'));
    }

    public function testRemoveExactlyToZeroDropsItem(): void
    {
        $b = MultiSet::any();
        $b->add('x', 3);
        self::assertSame(0, $b->remove('x', 3));
        self::assertFalse($b->contains('x'));
        self::assertSame(0, $b->countOf('x'));
    }

    public function testSetCountReplaces(): void
    {
        $b = MultiSet::any();
        $b->add('a', 4);
        $b->setCount('a', 1);
        self::assertSame(1, $b->countOf('a'));
        $b->setCount('a', 0);
        self::assertFalse($b->contains('a'));
    }

    public function testSetCountOnNewItemStoresTheItemForIteration(): void
    {
        $b = MultiSet::any();
        $b->setCount('brand-new', 5);
        self::assertSame(5, $b->countOf('brand-new'));
        self::assertSame(['brand-new'], $b->unique());
    }

    public function testSetCountValidatesType(): void
    {
        $b = MultiSet::ofInt();
        $this->expectException(InvalidArgumentException::class);
        $b->setCount('not-int', 5); // @phpstan-ignore argument.type (runtime rejection test)
    }

    public function testSetCountRejectsNegative(): void
    {
        $b = MultiSet::any();
        $this->expectException(InvalidArgumentException::class);
        $b->setCount('a', -1);
    }

    public function testAddRejectsNonPositiveCount(): void
    {
        $b = MultiSet::any();
        $this->expectException(InvalidArgumentException::class);
        $b->add('a', 0);
    }

    public function testRemoveRejectsNonPositiveCount(): void
    {
        $b = MultiSet::any();
        $this->expectException(InvalidArgumentException::class);
        $b->remove('a', 0);
    }

    public function testInitialItemsCountedAsOccurrences(): void
    {
        $b = MultiSet::any(['a', 'b', 'a', 'c', 'a']);
        self::assertSame(3, $b->countOf('a'));
        self::assertSame(1, $b->countOf('b'));
        self::assertSame(1, $b->countOf('c'));
        self::assertSame(3, $b->distinct());
        self::assertSame(5, $b->count());
    }

    public function testUniqueReturnsInsertionOrder(): void
    {
        $b = MultiSet::any(['b', 'a', 'a', 'c', 'b']);
        self::assertSame(['b', 'a', 'c'], $b->unique());
    }

    public function testMostCommon(): void
    {
        $b = MultiSet::any(['a', 'a', 'a', 'b', 'b', 'c']);
        self::assertSame([['a', 3], ['b', 2]], $b->mostCommon(2));
        self::assertSame([['a', 3], ['b', 2], ['c', 1]], $b->mostCommon(10));
        self::assertSame([], $b->mostCommon(0));
    }

    public function testMostCommonSortsByCountRegardlessOfInsertionOrder(): void
    {
        // Insertion order (low, mid, high) is the reverse of count order, so a
        // sort that leaked insertion order back in would misplace these.
        $b = MultiSet::any();
        $b->add('low', 1);
        $b->add('mid', 2);
        $b->add('high', 5);
        self::assertSame([['high', 5], ['mid', 2], ['low', 1]], $b->mostCommon(3));
    }

    public function testMostCommonTiesPreserveInsertionOrder(): void
    {
        $b = MultiSet::any();
        $b->add('first');
        $b->add('second');
        $b->add('third');
        self::assertSame([['first', 1], ['second', 1]], $b->mostCommon(2));
    }

    public function testMostCommonRejectsNegativeLimit(): void
    {
        $b = MultiSet::any();
        $this->expectException(InvalidArgumentException::class);
        $b->mostCommon(-1);
    }

    public function testTypeEnforcement(): void
    {
        $b = MultiSet::ofInt();
        $b->add(1);
        $this->expectException(InvalidArgumentException::class);
        $b->add('not-int'); // @phpstan-ignore argument.type (runtime rejection test)
    }

    public function testObjectIdentity(): void
    {
        $b = MultiSet::any();
        $a = new stdClass();
        $b->add($a);
        $b->add($a);
        $b->add(new stdClass());
        self::assertSame(2, $b->countOf($a));
        self::assertSame(2, $b->distinct());
    }

    public function testCurrentPastEndReturnsNull(): void
    {
        $b = MultiSet::any(['a']);
        $b->rewind();
        $b->next(); // past the single item
        self::assertNull($b->current());
    }

    public function testKeyPastEndReturnsZero(): void
    {
        $b = MultiSet::any(['a']);
        $b->rewind();
        $b->next(); // past the single item
        self::assertSame(0, $b->key());
    }

    public function testIterationYieldsItemsAndCounts(): void
    {
        $b = MultiSet::any(['a', 'b', 'a']);
        $pairs = [];
        foreach ($b as $count => $item) {
            $pairs[] = [$item, $count];
        }
        self::assertSame([['a', 2], ['b', 1]], $pairs);
    }

    public function testToArrayPairs(): void
    {
        $b = MultiSet::any(['a', 'a', 'b']);
        self::assertSame([['a', 2], ['b', 1]], $b->toArray());
    }

    public function testClear(): void
    {
        $b = MultiSet::any(['a', 'a', 'b']);
        $b->clear();
        self::assertTrue($b->isEmpty());
        self::assertSame(0, $b->count());
        self::assertSame(0, $b->distinct());
    }

    public function testScalarsDistinctFromSameLookingDifferentType(): void
    {
        $b = MultiSet::any();
        $b->add(1);
        $b->add('1');
        self::assertSame(1, $b->countOf(1));
        self::assertSame(1, $b->countOf('1'));
        self::assertSame(2, $b->distinct());
    }
}
