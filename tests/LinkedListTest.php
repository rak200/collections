<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\LinkedList;
use Rak200\Collections\LinkedNode;
use Rak200\Collections\Vector;

final class LinkedListTest extends TestCase {

    public function testEmptyListState(): void {
        $list = new LinkedList();
        self::assertCount(0, $list);
        self::assertNull($list->head());
        self::assertNull($list->tail());
        self::assertNull($list->pop());
        self::assertNull($list->shift());
        self::assertSame([], $list->toArray());
    }

    public function testPushAppendsAndReturnsNode(): void {
        $list = new LinkedList();
        $a = $list->push('a');
        $b = $list->push('b');
        self::assertInstanceOf(LinkedNode::class, $a);
        self::assertSame('a', $a->value);
        self::assertSame($a, $list->head());
        self::assertSame($b, $list->tail());
        self::assertCount(2, $list);
    }

    public function testUnshiftPrependsAndAdjustsHead(): void {
        $list = new LinkedList();
        $list->push('b');
        $a = $list->unshift('a');
        self::assertSame($a, $list->head());
        self::assertSame(['a', 'b'], $list->toArray());
    }

    public function testPopRemovesTail(): void {
        $list = new LinkedList('mixed', ['a', 'b', 'c']);
        self::assertSame('c', $list->pop());
        self::assertCount(2, $list);
        self::assertSame('b', $list->tail()->value);
    }

    public function testShiftRemovesHead(): void {
        $list = new LinkedList('mixed', ['a', 'b', 'c']);
        self::assertSame('a', $list->shift());
        self::assertCount(2, $list);
        self::assertSame('b', $list->head()->value);
    }

    public function testInsertBeforeAtMiddle(): void {
        $list = new LinkedList();
        $a = $list->push('a');
        $c = $list->push('c');
        $b = $list->insertBefore($c, 'b');
        self::assertSame(['a', 'b', 'c'], $list->toArray());
        self::assertSame($a, $b->prev);
        self::assertSame($c, $b->next);
    }

    public function testInsertBeforeAtHeadUpdatesHead(): void {
        $list = new LinkedList();
        $a = $list->push('a');
        $z = $list->insertBefore($a, 'z');
        self::assertSame($z, $list->head());
        self::assertSame(['z', 'a'], $list->toArray());
    }

    public function testInsertAfterAtMiddle(): void {
        $list = new LinkedList();
        $a = $list->push('a');
        $c = $list->push('c');
        $b = $list->insertAfter($a, 'b');
        self::assertSame(['a', 'b', 'c'], $list->toArray());
        self::assertSame($a, $b->prev);
        self::assertSame($c, $b->next);
    }

    public function testInsertAfterAtTailUpdatesTail(): void {
        $list = new LinkedList();
        $a = $list->push('a');
        $b = $list->insertAfter($a, 'b');
        self::assertSame($b, $list->tail());
    }

    public function testRemoveMiddleNode(): void {
        $list = new LinkedList();
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

    public function testRemoveHead(): void {
        $list = new LinkedList();
        $a = $list->push('a');
        $b = $list->push('b');
        $list->remove($a);
        self::assertSame($b, $list->head());
        self::assertCount(1, $list);
    }

    public function testRemoveTail(): void {
        $list = new LinkedList();
        $a = $list->push('a');
        $b = $list->push('b');
        $list->remove($b);
        self::assertSame($a, $list->tail());
        self::assertCount(1, $list);
    }

    public function testRemoveOnlyNodeEmptiesList(): void {
        $list = new LinkedList();
        $only = $list->push('a');
        $list->remove($only);
        self::assertCount(0, $list);
        self::assertNull($list->head());
        self::assertNull($list->tail());
    }

    public function testIterationOrder(): void {
        $list = new LinkedList('mixed', ['a', 'b', 'c']);
        $out = [];
        foreach ($list as $k => $v) {
            $out[$k] = $v;
        }
        self::assertSame([0 => 'a', 1 => 'b', 2 => 'c'], $out);
    }

    public function testTypeEnforcementOnPush(): void {
        $list = new LinkedList(\stdClass::class);
        $this->expectException(InvalidArgumentException::class);
        $list->push('not-an-object');
    }

    public function testTypeEnforcementAcceptsInstance(): void {
        $obj = new \stdClass();
        $list = new LinkedList(\stdClass::class);
        $list->push($obj);
        self::assertSame($obj, $list->head()->value);
    }

    public function testMixedTypeAcceptsScalars(): void {
        $list = new LinkedList('mixed');
        $list->push(42);
        $list->push('str');
        $list->push(null);
        self::assertSame([42, 'str', null], $list->toArray());
    }

    public function testFromVector(): void {
        $vector = new Vector('mixed', [10, 20, 30]);
        $list = LinkedList::fromVector($vector);
        self::assertSame('mixed', $list->getType());
        self::assertSame([10, 20, 30], $list->toArray());
    }
}
