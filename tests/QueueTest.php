<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\Queue;

final class QueueTest extends TestCase {

    public function testEmptyQueueState(): void {
        $q = new Queue();
        self::assertCount(0, $q);
        self::assertNull($q->dequeue());
        self::assertNull($q->peek());
        self::assertSame([], $q->toArray());
    }

    public function testEnqueueAndDequeueAreFIFO(): void {
        $q = new Queue();
        $a = new \stdClass();
        $b = new \stdClass();
        $c = new \stdClass();
        $q->enqueue($a);
        $q->enqueue($b);
        $q->enqueue($c);
        self::assertSame($a, $q->dequeue());
        self::assertSame($b, $q->dequeue());
        self::assertSame($c, $q->dequeue());
        self::assertNull($q->dequeue());
    }

    public function testPeekDoesNotRemove(): void {
        $q = new Queue();
        $a = new \stdClass();
        $q->enqueue($a);
        self::assertSame($a, $q->peek());
        self::assertCount(1, $q);
    }

    public function testClassTypeRejectsNonObject(): void {
        $q = new Queue(\stdClass::class);
        $q->enqueue(new \stdClass());
        $this->expectException(InvalidArgumentException::class);
        $q->enqueue('not-an-object');
    }

    public function testClassTypeRejectsWrongInstance(): void {
        $q = new Queue(\DateTimeImmutable::class);
        $this->expectException(InvalidArgumentException::class);
        $q->enqueue(new \stdClass());
    }

    public function testMixedAcceptsScalarsAndNull(): void {
        $q = new Queue('mixed');
        $q->enqueue(42);
        $q->enqueue('hello');
        $q->enqueue(null);
        self::assertCount(3, $q);
        self::assertSame(42, $q->dequeue());
        self::assertSame('hello', $q->dequeue());
        self::assertNull($q->dequeue());
        self::assertCount(0, $q);
    }

    public function testInitialItems(): void {
        $a = new \stdClass();
        $b = new \stdClass();
        $q = new Queue('mixed', [$a, $b]);
        self::assertCount(2, $q);
        self::assertSame($a, $q->dequeue());
    }

    public function testIterationOrder(): void {
        $a = new \stdClass();
        $b = new \stdClass();
        $c = new \stdClass();
        $q = new Queue('mixed', [$a, $b, $c]);
        $out = [];
        foreach ($q as $item) {
            $out[] = $item;
        }
        self::assertSame([$a, $b, $c], $out);
    }
}
