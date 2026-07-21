<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\Internal\LinkedNode;
use Rak200\Collections\LinkedList;
use Rak200\Collections\Vector;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class LinkedListTest extends TestCase
{
    public function testEmptyListState(): void
    {
        $list = LinkedList::any();
        self::assertCount(0, $list);
        self::assertNull($list->head());
        self::assertNull($list->tail());
        self::assertNull($list->pop());
        self::assertNull($list->shift());
        self::assertSame([], $list->toArray());
    }

    public function testPushAppendsAndReturnsNode(): void
    {
        $list = LinkedList::any();
        $a = $list->push('a');
        $b = $list->push('b');
        self::assertInstanceOf(LinkedNode::class, $a);
        self::assertSame('a', $a->value);
        self::assertSame($a, $list->head());
        self::assertSame($b, $list->tail());
        self::assertCount(2, $list);
    }

    public function testUnshiftPrependsAndAdjustsHead(): void
    {
        $list = LinkedList::any();
        $list->push('b');
        $a = $list->unshift('a');
        self::assertSame($a, $list->head());
        self::assertSame(['a', 'b'], $list->toArray());
        self::assertCount(2, $list);
    }

    public function testUnshiftIncrementsCount(): void
    {
        $list = LinkedList::any();
        self::assertCount(0, $list);
        $list->unshift('a');
        self::assertCount(1, $list);
        $list->unshift('b');
        self::assertCount(2, $list);
    }

    public function testPopRemovesTail(): void
    {
        $list = LinkedList::any(['a', 'b', 'c']);
        self::assertSame('c', $list->pop());
        self::assertCount(2, $list);
        $tail = $list->tail();
        self::assertNotNull($tail);
        self::assertSame('b', $tail->value);
    }

    public function testShiftRemovesHead(): void
    {
        $list = LinkedList::any(['a', 'b', 'c']);
        self::assertSame('a', $list->shift());
        self::assertCount(2, $list);
        $head = $list->head();
        self::assertNotNull($head);
        self::assertSame('b', $head->value);
    }

    public function testInsertBeforeAtMiddle(): void
    {
        $list = LinkedList::any();
        $a = $list->push('a');
        $c = $list->push('c');
        $b = $list->insertBefore($c, 'b');
        self::assertSame(['a', 'b', 'c'], $list->toArray());
        self::assertSame($a, $b->prev);
        self::assertSame($c, $b->next);
        self::assertCount(3, $list);
    }

    public function testInsertBeforeRejectsWrongType(): void
    {
        $list = LinkedList::of(stdClass::class);
        $node = $list->push(new stdClass());
        $this->expectException(InvalidArgumentException::class);
        $list->insertBefore($node, 'not-an-object'); // @phpstan-ignore argument.type (runtime rejection test)
    }

    public function testInsertBeforeAtHeadUpdatesHead(): void
    {
        $list = LinkedList::any();
        $a = $list->push('a');
        $z = $list->insertBefore($a, 'z');
        self::assertSame($z, $list->head());
        self::assertSame(['z', 'a'], $list->toArray());
    }

    public function testInsertAfterAtMiddle(): void
    {
        $list = LinkedList::any();
        $a = $list->push('a');
        $c = $list->push('c');
        $b = $list->insertAfter($a, 'b');
        self::assertSame(['a', 'b', 'c'], $list->toArray());
        self::assertSame($a, $b->prev);
        self::assertSame($c, $b->next);
        self::assertCount(3, $list);
    }

    public function testInsertAfterRejectsWrongType(): void
    {
        $list = LinkedList::of(stdClass::class);
        $node = $list->push(new stdClass());
        $this->expectException(InvalidArgumentException::class);
        $list->insertAfter($node, 'not-an-object'); // @phpstan-ignore argument.type (runtime rejection test)
    }

    public function testInsertAfterAtTailUpdatesTail(): void
    {
        $list = LinkedList::any();
        $a = $list->push('a');
        $b = $list->insertAfter($a, 'b');
        self::assertSame($b, $list->tail());
    }

    public function testRemoveMiddleNode(): void
    {
        $list = LinkedList::any();
        $a = $list->push('a');
        $b = $list->push('b');
        $c = $list->push('c');
        $list->remove($b);
        self::assertSame(['a', 'c'], $list->toArray());
        self::assertSame($a, $list->head());
        self::assertSame($c, $list->tail());
        self::assertNull($b->prev);
        self::assertNull($b->next);
    }

    public function testRemoveHead(): void
    {
        $list = LinkedList::any();
        $a = $list->push('a');
        $b = $list->push('b');
        $list->remove($a);
        self::assertSame($b, $list->head());
        self::assertCount(1, $list);
    }

    public function testRemoveTail(): void
    {
        $list = LinkedList::any();
        $a = $list->push('a');
        $b = $list->push('b');
        $list->remove($b);
        self::assertSame($a, $list->tail());
        self::assertCount(1, $list);
    }

    public function testRemoveOnlyNodeEmptiesList(): void
    {
        $list = LinkedList::any();
        $only = $list->push('a');
        $list->remove($only);
        self::assertCount(0, $list);
        self::assertNull($list->head());
        self::assertNull($list->tail());
    }

    public function testIterationOrder(): void
    {
        $list = LinkedList::any(['a', 'b', 'c']);
        $out = [];
        foreach ($list as $k => $v) {
            $out[$k] = $v;
        }
        self::assertSame([0 => 'a', 1 => 'b', 2 => 'c'], $out);
    }

    #[DataProvider('rejectedItemProvider')]
    public function testTypeEnforcementOnPush(string $type, mixed $wrong): void
    {
        $list = new LinkedList($type);
        $this->expectException(InvalidArgumentException::class);
        $list->push($wrong);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function rejectedItemProvider(): iterable
    {
        yield 'non-object into stdClass list' => [stdClass::class, 'not-an-object'];
    }

    public function testTypeEnforcementAcceptsInstance(): void
    {
        $obj = new stdClass();
        $list = LinkedList::of(stdClass::class);
        $list->push($obj);
        $head = $list->head();
        self::assertNotNull($head);
        self::assertSame($obj, $head->value);
    }

    public function testMixedTypeAcceptsScalars(): void
    {
        $list = LinkedList::any();
        $list->push(42);
        $list->push('str');
        $list->push(null);
        self::assertSame([42, 'str', null], $list->toArray());
    }

    public function testFromVector(): void
    {
        $vector = Vector::any([10, 20, 30]);
        $list = LinkedList::fromVector($vector);
        self::assertSame('mixed', $list->getType());
        self::assertSame([10, 20, 30], $list->toArray());
    }

    public function testIsEmpty(): void
    {
        $list = LinkedList::any();
        self::assertTrue($list->isEmpty());
        $list->push('a');
        self::assertFalse($list->isEmpty());
        $list->pop();
        self::assertTrue($list->isEmpty());
    }

    public function testClearResetsAllState(): void
    {
        $list = LinkedList::any(['a', 'b', 'c']);
        $list->clear();
        self::assertCount(0, $list);
        self::assertNull($list->head());
        self::assertNull($list->tail());
        self::assertSame([], $list->toArray());
        $list->push('x');
        self::assertSame(['x'], $list->toArray());
    }

    public function testRemoveRejectsForeignNode(): void
    {
        $listA = LinkedList::any();
        $listB = LinkedList::any();
        $node = $listA->push('a');
        $this->expectException(InvalidArgumentException::class);
        $listB->remove($node);
    }

    public function testRemovingSameNodeTwiceDoesNotUnderflowCount(): void
    {
        $list = LinkedList::ofInt([1]);
        $node = $list->head();
        self::assertNotNull($node);
        $list->remove($node);
        self::assertSame(0, $list->count());
        // The node is now detached but still belongs to $list; removing it
        // again must be a no-op, not push the count negative.
        $list->remove($node);
        self::assertSame(0, $list->count());
    }

    public function testClearResetsIterationPosition(): void
    {
        $list = LinkedList::ofInt([1, 2]);
        $list->clear();
        self::assertSame(0, $list->key());
    }

    public function testCurrentReturnsNullPastEnd(): void
    {
        $list = LinkedList::ofInt([1]);
        $list->rewind();
        $list->next(); // cursor becomes null (past the single element)
        self::assertNull($list->current());
    }

    public function testNextPastEndStaysNullWithoutWarning(): void
    {
        $list = LinkedList::ofInt([1]);
        $list->rewind();
        $list->next(); // cursor now null
        $list->next(); // calling next() again past the end must be a no-op
        self::assertNull($list->current());
        self::assertFalse($list->valid());
    }
}
