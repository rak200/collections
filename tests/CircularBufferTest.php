<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\CircularBuffer;

/**
 * @internal
 *
 * @coversNothing
 */
final class CircularBufferTest extends TestCase
{
    use ConstructsProtected;

    public function testConstructorRejectsNonPositiveCapacity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CircularBuffer::any(0);
    }

    public function testCapacityOfOneIsValid(): void
    {
        $b = CircularBuffer::any(1);
        self::assertSame(1, $b->capacity());
        self::assertNull($b->push('a'));
        self::assertSame(['a'], $b->toArray());
    }

    public function testEmptyState(): void
    {
        $b = CircularBuffer::any(3);
        self::assertCount(0, $b);
        self::assertTrue($b->isEmpty());
        self::assertFalse($b->isFull());
        self::assertSame(3, $b->capacity());
        self::assertNull($b->pop());
        self::assertNull($b->peek());
        self::assertSame([], $b->toArray());
        self::assertSame('mixed', $b->getType());
    }

    public function testPushUntilFullDoesNotEvict(): void
    {
        $b = CircularBuffer::any(3);
        self::assertNull($b->push('a'));
        self::assertNull($b->push('b'));
        self::assertNull($b->push('c'));
        self::assertTrue($b->isFull());
        self::assertSame(['a', 'b', 'c'], $b->toArray());
    }

    public function testPushPastFullEvictsOldest(): void
    {
        $b = CircularBuffer::any(3);
        $b->push('a');
        $b->push('b');
        $b->push('c');
        self::assertSame('a', $b->push('d'));
        self::assertSame('b', $b->push('e'));
        self::assertSame(['c', 'd', 'e'], $b->toArray());
    }

    public function testPopReturnsOldest(): void
    {
        $b = CircularBuffer::any(3, ['a', 'b', 'c']);
        self::assertSame('a', $b->pop());
        self::assertSame('b', $b->pop());
        self::assertSame('c', $b->pop());
        self::assertNull($b->pop());
    }

    public function testPeekReturnsOldestWithoutRemoval(): void
    {
        $b = CircularBuffer::any(3, ['a', 'b', 'c']);
        self::assertSame('a', $b->peek());
        self::assertCount(3, $b);
    }

    public function testPushPopInterleaving(): void
    {
        $b = CircularBuffer::any(3);
        $b->push('a');
        $b->push('b');
        self::assertSame('a', $b->pop());
        $b->push('c');
        $b->push('d');
        self::assertSame(['b', 'c', 'd'], $b->toArray());
    }

    public function testWrapAround(): void
    {
        $b = CircularBuffer::any(3);
        $b->push('a');
        $b->push('b');
        $b->push('c');
        $b->pop();           // remove 'a' — head moves to slot 1
        $b->push('d');       // wraps to slot 0
        $b->push('e');       // overwrites 'b'
        self::assertSame(['c', 'd', 'e'], $b->toArray());
    }

    #[DataProvider('rejectedItemProvider')]
    public function testTypeEnforcement(string $type, mixed $ok, mixed $wrong): void
    {
        $b = self::build(CircularBuffer::class, 2, $type);
        $b->push($ok);
        $this->expectException(InvalidArgumentException::class);
        $b->push($wrong);
    }

    /** @return iterable<string, array{string, mixed, mixed}> */
    public static function rejectedItemProvider(): iterable
    {
        yield 'string into int buffer' => ['int', 1, 'not-int'];
    }

    public function testInitialItemsRespectCapacity(): void
    {
        $b = CircularBuffer::any(2, ['a', 'b', 'c']);
        self::assertSame(['b', 'c'], $b->toArray());
    }

    public function testIteration(): void
    {
        $b = CircularBuffer::any(3, ['a', 'b', 'c']);
        $b->push('d');  // evicts 'a'
        $out = [];
        foreach ($b as $i => $v) {
            $out[$i] = $v;
        }
        self::assertSame([0 => 'b', 1 => 'c', 2 => 'd'], $out);
    }

    public function testClear(): void
    {
        $b = CircularBuffer::any(3, ['a', 'b']);
        $b->clear();
        self::assertTrue($b->isEmpty());
        self::assertSame([], $b->toArray());
        $b->push('x');
        self::assertSame(['x'], $b->toArray());
    }

    public function testClearResetsIterationPosition(): void
    {
        $b = CircularBuffer::any(2, ['a', 'b']);
        $b->clear();
        self::assertFalse($b->valid());
        self::assertNull($b->current());
    }

    public function testClearResetsHeadForFreshWrapping(): void
    {
        $b = CircularBuffer::any(2, [1, 2]);
        $b->clear();
        self::assertNull($b->push('a'));
        self::assertNull($b->push('b'));
        self::assertSame('a', $b->push('c')); // evicts the oldest of the fresh cycle
        self::assertSame(['b', 'c'], $b->toArray());
    }

    public function testCurrentReturnsNullPastLastPosition(): void
    {
        $b = CircularBuffer::any(2, ['a', 'b']);
        $b->rewind();
        $b->next();
        $b->next(); // iterPos now equals count
        self::assertNull($b->current());
    }

    public function testMixedAcceptsScalars(): void
    {
        $b = CircularBuffer::any(3);
        $b->push(1);
        $b->push('two');
        $b->push(null);
        self::assertSame([1, 'two', null], $b->toArray());
    }
}
