<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\AbstractCollection;
use Rak200\Collections\Stack;

final class StackTest extends TestCase {

    public function testEmptyStackState(): void {
        $s = new Stack();
        self::assertCount(0, $s);
        self::assertNull($s->pop());
        self::assertNull($s->peek());
        self::assertSame([], $s->toArray());
    }

    public function testPushPopIsLIFO(): void {
        $s = new Stack();
        $a = new \stdClass();
        $b = new \stdClass();
        $c = new \stdClass();
        $s->push($a);
        $s->push($b);
        $s->push($c);
        self::assertSame($c, $s->pop());
        self::assertSame($b, $s->pop());
        self::assertSame($a, $s->pop());
        self::assertNull($s->pop());
    }

    public function testPeekReturnsTopWithoutRemoving(): void {
        $s = new Stack();
        $a = new \stdClass();
        $b = new \stdClass();
        $s->push($a);
        $s->push($b);
        self::assertSame($b, $s->peek());
        self::assertCount(2, $s);
    }

    public function testIterationIsTopToBottom(): void {
        $s = new Stack();
        $a = new \stdClass();
        $b = new \stdClass();
        $c = new \stdClass();
        $s->push($a);
        $s->push($b);
        $s->push($c);
        $out = [];
        foreach ($s as $position => $item) {
            $out[$position] = $item;
        }
        self::assertSame([0 => $c, 1 => $b, 2 => $a], $out);
    }

    public function testTypeEnforcementRejectsWrongInstance(): void {
        $s = new Stack(\DateTimeImmutable::class);
        $this->expectException(InvalidArgumentException::class);
        $s->push(new \stdClass());
    }

    public function testInitialItemsArePushedInOrder(): void {
        $a = new \stdClass();
        $b = new \stdClass();
        $s = new Stack('mixed', [$a, $b]);
        self::assertSame($b, $s->peek());
        self::assertCount(2, $s);
    }

    public function testIsAbstractCollection(): void {
        self::assertInstanceOf(AbstractCollection::class, new Stack());
    }

    public function testMixedAcceptsScalarsAndNull(): void {
        $s = new Stack('mixed');
        $s->push(42);
        $s->push('top');
        $s->push(null);
        self::assertCount(3, $s);
        self::assertNull($s->pop());
        self::assertSame('top', $s->pop());
        self::assertSame(42, $s->pop());
    }
}
