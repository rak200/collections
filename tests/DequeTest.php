<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\Deque;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class DequeTest extends TestCase
{
    public function testEmptyState(): void
    {
        $d = Deque::any();
        self::assertCount(0, $d);
        self::assertTrue($d->isEmpty());
        self::assertNull($d->popFront());
        self::assertNull($d->popBack());
        self::assertNull($d->peekFront());
        self::assertNull($d->peekBack());
        self::assertSame([], $d->toArray());
        self::assertSame('mixed', $d->getType());
    }

    public function testPushBackAndPopFrontIsFIFO(): void
    {
        $d = Deque::any();
        $d->pushBack('a');
        $d->pushBack('b');
        $d->pushBack('c');
        self::assertSame('a', $d->popFront());
        self::assertSame('b', $d->popFront());
        self::assertSame('c', $d->popFront());
        self::assertNull($d->popFront());
    }

    public function testPushFrontAndPopFrontIsLIFO(): void
    {
        $d = Deque::any();
        $d->pushFront('a');
        $d->pushFront('b');
        $d->pushFront('c');
        self::assertSame('c', $d->popFront());
        self::assertSame('b', $d->popFront());
        self::assertSame('a', $d->popFront());
    }

    public function testPopBackRemovesFromTheBack(): void
    {
        $d = Deque::any();
        $d->pushBack('a');
        $d->pushBack('b');
        $d->pushBack('c');
        self::assertSame('c', $d->popBack());
        self::assertSame('b', $d->popBack());
        self::assertSame('a', $d->popBack());
    }

    public function testPeekDoesNotRemove(): void
    {
        $d = Deque::any();
        $d->pushBack('a');
        $d->pushBack('b');
        self::assertSame('a', $d->peekFront());
        self::assertSame('b', $d->peekBack());
        self::assertCount(2, $d);
    }

    #[DataProvider('rejectedItemProvider')]
    public function testTypeEnforcement(string $type, mixed $wrong): void
    {
        $d = new Deque($type);
        $d->pushBack(new stdClass());
        $this->expectException(InvalidArgumentException::class);
        $d->pushFront($wrong);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function rejectedItemProvider(): iterable
    {
        yield 'non-object into stdClass deque' => [stdClass::class, 'not-an-object'];
    }

    public function testInitialItemsPushedToBack(): void
    {
        $d = Deque::any(['a', 'b', 'c']);
        self::assertSame('a', $d->peekFront());
        self::assertSame('c', $d->peekBack());
        self::assertSame(['a', 'b', 'c'], $d->toArray());
    }

    public function testIteration(): void
    {
        $d = Deque::any(['a', 'b']);
        $d->pushFront('z');
        $out = [];
        foreach ($d as $v) {
            $out[] = $v;
        }
        self::assertSame(['z', 'a', 'b'], $out);
    }

    public function testClear(): void
    {
        $d = Deque::any(['a', 'b']);
        $d->clear();
        self::assertTrue($d->isEmpty());
        self::assertNull($d->peekFront());
    }

    public function testMixedAcceptsScalarsAndNull(): void
    {
        $d = Deque::any();
        $d->pushBack(1);
        $d->pushBack('two');
        $d->pushFront(null);
        self::assertSame([null, 1, 'two'], $d->toArray());
    }
}
