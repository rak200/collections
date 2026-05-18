<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\Deque;

final class DequeTest extends TestCase {

    public function testEmptyState(): void {
        $d = new Deque();
        self::assertCount(0, $d);
        self::assertTrue($d->isEmpty());
        self::assertNull($d->popFront());
        self::assertNull($d->popBack());
        self::assertNull($d->peekFront());
        self::assertNull($d->peekBack());
        self::assertSame([], $d->toArray());
        self::assertSame('mixed', $d->getType());
    }

    public function testPushBackAndPopFrontIsFIFO(): void {
        $d = new Deque();
        $d->pushBack('a');
        $d->pushBack('b');
        $d->pushBack('c');
        self::assertSame('a', $d->popFront());
        self::assertSame('b', $d->popFront());
        self::assertSame('c', $d->popFront());
        self::assertNull($d->popFront());
    }

    public function testPushFrontAndPopFrontIsLIFO(): void {
        $d = new Deque();
        $d->pushFront('a');
        $d->pushFront('b');
        $d->pushFront('c');
        self::assertSame('c', $d->popFront());
        self::assertSame('b', $d->popFront());
        self::assertSame('a', $d->popFront());
    }

    public function testPopBackRemovesFromTheBack(): void {
        $d = new Deque();
        $d->pushBack('a');
        $d->pushBack('b');
        $d->pushBack('c');
        self::assertSame('c', $d->popBack());
        self::assertSame('b', $d->popBack());
        self::assertSame('a', $d->popBack());
    }

    public function testPeekDoesNotRemove(): void {
        $d = new Deque();
        $d->pushBack('a');
        $d->pushBack('b');
        self::assertSame('a', $d->peekFront());
        self::assertSame('b', $d->peekBack());
        self::assertCount(2, $d);
    }

    public function testTypeEnforcement(): void {
        $d = new Deque(\stdClass::class);
        $d->pushBack(new \stdClass());
        $this->expectException(InvalidArgumentException::class);
        $d->pushFront('not-an-object');
    }

    public function testInitialItemsPushedToBack(): void {
        $d = new Deque('mixed', ['a', 'b', 'c']);
        self::assertSame('a', $d->peekFront());
        self::assertSame('c', $d->peekBack());
        self::assertSame(['a', 'b', 'c'], $d->toArray());
    }

    public function testIteration(): void {
        $d = new Deque('mixed', ['a', 'b']);
        $d->pushFront('z');
        $out = [];
        foreach ($d as $v) {
            $out[] = $v;
        }
        self::assertSame(['z', 'a', 'b'], $out);
    }

    public function testClear(): void {
        $d = new Deque('mixed', ['a', 'b']);
        $d->clear();
        self::assertTrue($d->isEmpty());
        self::assertNull($d->peekFront());
    }

    public function testMixedAcceptsScalarsAndNull(): void {
        $d = new Deque();
        $d->pushBack(1);
        $d->pushBack('two');
        $d->pushFront(null);
        self::assertSame([null, 1, 'two'], $d->toArray());
    }
}
