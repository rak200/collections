<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\Queue;

final class QueueTest extends TestCase {

    public function testEmptyQueueState(): void {
        $q = Queue::any();
        self::assertCount(0, $q);
        self::assertNull($q->dequeue());
        self::assertNull($q->peek());
        self::assertSame([], $q->toArray());
    }

    public function testEnqueueAndDequeueAreFIFO(): void {
        $q = Queue::any();
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
        $q = Queue::any();
        $a = new \stdClass();
        $q->enqueue($a);
        self::assertSame($a, $q->peek());
        self::assertCount(1, $q);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function rejectedItemProvider(): iterable {
        yield 'non-object into stdClass queue' => [\stdClass::class, 'not-an-object'];
        yield 'stdClass into DateTimeImmutable queue' => [\DateTimeImmutable::class, new \stdClass()];
    }

    #[DataProvider('rejectedItemProvider')]
    public function testClassTypeRejectsInvalidItem(string $type, mixed $wrong): void {
        $q = new Queue($type);
        $this->expectException(InvalidArgumentException::class);
        $q->enqueue($wrong);
    }

    public function testMixedAcceptsScalarsAndNull(): void {
        $q = Queue::any();
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
        $q = Queue::any([$a, $b]);
        self::assertCount(2, $q);
        self::assertSame($a, $q->dequeue());
    }

    public function testIterationOrder(): void {
        $a = new \stdClass();
        $b = new \stdClass();
        $c = new \stdClass();
        $q = Queue::any([$a, $b, $c]);
        $out = [];
        foreach ($q as $item) {
            $out[] = $item;
        }
        self::assertSame([$a, $b, $c], $out);
    }

    public function testIsEmptyAndClear(): void {
        $q = Queue::any();
        self::assertTrue($q->isEmpty());
        $q->enqueue('a');
        $q->enqueue('b');
        self::assertFalse($q->isEmpty());
        $q->clear();
        self::assertTrue($q->isEmpty());
        self::assertCount(0, $q);
        self::assertNull($q->peek());
    }
}
