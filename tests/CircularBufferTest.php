<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\CircularBuffer;

final class CircularBufferTest extends TestCase {

    public function testConstructorRejectsNonPositiveCapacity(): void {
        $this->expectException(InvalidArgumentException::class);
        new CircularBuffer(0);
    }

    public function testEmptyState(): void {
        $b = new CircularBuffer(3);
        self::assertCount(0, $b);
        self::assertTrue($b->isEmpty());
        self::assertFalse($b->isFull());
        self::assertSame(3, $b->capacity());
        self::assertNull($b->pop());
        self::assertNull($b->peek());
        self::assertSame([], $b->toArray());
        self::assertSame('mixed', $b->getType());
    }

    public function testPushUntilFullDoesNotEvict(): void {
        $b = new CircularBuffer(3);
        self::assertNull($b->push('a'));
        self::assertNull($b->push('b'));
        self::assertNull($b->push('c'));
        self::assertTrue($b->isFull());
        self::assertSame(['a', 'b', 'c'], $b->toArray());
    }

    public function testPushPastFullEvictsOldest(): void {
        $b = new CircularBuffer(3);
        $b->push('a');
        $b->push('b');
        $b->push('c');
        self::assertSame('a', $b->push('d'));
        self::assertSame('b', $b->push('e'));
        self::assertSame(['c', 'd', 'e'], $b->toArray());
    }

    public function testPopReturnsOldest(): void {
        $b = new CircularBuffer(3, 'mixed', ['a', 'b', 'c']);
        self::assertSame('a', $b->pop());
        self::assertSame('b', $b->pop());
        self::assertSame('c', $b->pop());
        self::assertNull($b->pop());
    }

    public function testPeekReturnsOldestWithoutRemoval(): void {
        $b = new CircularBuffer(3, 'mixed', ['a', 'b', 'c']);
        self::assertSame('a', $b->peek());
        self::assertCount(3, $b);
    }

    public function testPushPopInterleaving(): void {
        $b = new CircularBuffer(3);
        $b->push('a');
        $b->push('b');
        self::assertSame('a', $b->pop());
        $b->push('c');
        $b->push('d');
        self::assertSame(['b', 'c', 'd'], $b->toArray());
    }

    public function testWrapAround(): void {
        $b = new CircularBuffer(3);
        $b->push('a');
        $b->push('b');
        $b->push('c');
        $b->pop();           // remove 'a' — head moves to slot 1
        $b->push('d');       // wraps to slot 0
        $b->push('e');       // overwrites 'b'
        self::assertSame(['c', 'd', 'e'], $b->toArray());
    }

    public function testTypeEnforcement(): void {
        $b = new CircularBuffer(2, 'int');
        $b->push(1);
        $this->expectException(InvalidArgumentException::class);
        $b->push('not-int');
    }

    public function testInitialItemsRespectCapacity(): void {
        $b = new CircularBuffer(2, 'mixed', ['a', 'b', 'c']);
        self::assertSame(['b', 'c'], $b->toArray());
    }

    public function testIteration(): void {
        $b = new CircularBuffer(3, 'mixed', ['a', 'b', 'c']);
        $b->push('d');  // evicts 'a'
        $out = [];
        foreach ($b as $i => $v) {
            $out[$i] = $v;
        }
        self::assertSame([0 => 'b', 1 => 'c', 2 => 'd'], $out);
    }

    public function testClear(): void {
        $b = new CircularBuffer(3, 'mixed', ['a', 'b']);
        $b->clear();
        self::assertTrue($b->isEmpty());
        self::assertSame([], $b->toArray());
        $b->push('x');
        self::assertSame(['x'], $b->toArray());
    }

    public function testMixedAcceptsScalars(): void {
        $b = new CircularBuffer(3);
        $b->push(1);
        $b->push('two');
        $b->push(null);
        self::assertSame([1, 'two', null], $b->toArray());
    }
}
