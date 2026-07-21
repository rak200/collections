<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\PriorityQueue;
use ReflectionProperty;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class PriorityQueueTest extends TestCase
{
    public function testEmptyQueueState(): void
    {
        $pq = PriorityQueue::any();
        self::assertCount(0, $pq);
        self::assertNull($pq->peek());
        self::assertNull($pq->dequeue());
        self::assertSame([], $pq->toArray());
    }

    public function testHigherPriorityIsExtractedFirst(): void
    {
        $pq = PriorityQueue::any();
        $low = new stdClass();
        $mid = new stdClass();
        $high = new stdClass();
        $pq->enqueue($low, 1);
        $pq->enqueue($high, 10);
        $pq->enqueue($mid, 5);
        self::assertSame($high, $pq->dequeue());
        self::assertSame($mid, $pq->dequeue());
        self::assertSame($low, $pq->dequeue());
    }

    public function testTiesBreakFIFOByInsertionOrder(): void
    {
        $pq = PriorityQueue::any();
        $a = new stdClass();
        $b = new stdClass();
        $c = new stdClass();
        $pq->enqueue($a, 5);
        $pq->enqueue($b, 5);
        $pq->enqueue($c, 5);
        self::assertSame($a, $pq->dequeue());
        self::assertSame($b, $pq->dequeue());
        self::assertSame($c, $pq->dequeue());
    }

    public function testPeekDoesNotMutate(): void
    {
        $pq = PriorityQueue::any();
        $a = new stdClass();
        $pq->enqueue($a, 1);
        self::assertSame($a, $pq->peek());
        self::assertCount(1, $pq);
    }

    public function testFloatPrioritiesWork(): void
    {
        $pq = PriorityQueue::any();
        $a = new stdClass();
        $b = new stdClass();
        $pq->enqueue($a, 1.5);
        $pq->enqueue($b, 2.7);
        self::assertSame($b, $pq->dequeue());
        self::assertSame($a, $pq->dequeue());
    }

    public function testTypeEnforcement(): void
    {
        $pq = PriorityQueue::of(DateTimeImmutable::class);
        $this->expectException(InvalidArgumentException::class);
        $pq->enqueue(new stdClass(), 1); // @phpstan-ignore argument.type (runtime rejection test)
    }

    public function testIterationIsNonDestructiveAndInPriorityOrder(): void
    {
        $pq = PriorityQueue::any();
        $low = new stdClass();
        $high = new stdClass();
        $mid = new stdClass();
        $pq->enqueue($low, 1);
        $pq->enqueue($high, 10);
        $pq->enqueue($mid, 5);

        $first = [];
        foreach ($pq as $item) {
            $first[] = $item;
        }
        $second = [];
        foreach ($pq as $item) {
            $second[] = $item;
        }

        self::assertSame([$high, $mid, $low], $first);
        self::assertSame($first, $second);
        self::assertCount(3, $pq);
    }

    public function testToArrayTiesBreakFIFOByInsertionOrder(): void
    {
        $pq = PriorityQueue::any();
        $a = new stdClass();
        $b = new stdClass();
        $c = new stdClass();
        $pq->enqueue($a, 5);
        $pq->enqueue($b, 5);
        $pq->enqueue($c, 5);
        self::assertSame([$a, $b, $c], $pq->toArray());
    }

    public function testToArrayReturnsExtractionOrderWithoutMutating(): void
    {
        $pq = PriorityQueue::any();
        $a = new stdClass();
        $b = new stdClass();
        $pq->enqueue($a, 1);
        $pq->enqueue($b, 9);
        self::assertSame([$b, $a], $pq->toArray());
        self::assertCount(2, $pq);
    }

    public function testSeededItemsUsePriorityZero(): void
    {
        $pq = PriorityQueue::ofString(['seeded']);
        $pq->enqueue('higher', 0.5);
        $pq->enqueue('lower', -0.5);
        self::assertSame('higher', $pq->dequeue());
        self::assertSame('seeded', $pq->dequeue());
        self::assertSame('lower', $pq->dequeue());
    }

    public function testClearResetsIterationKeyPosition(): void
    {
        $pq = PriorityQueue::any(['a', 'b']);
        $pq->rewind();
        $pq->next();
        $pq->clear();
        self::assertSame(0, $pq->key());
    }

    public function testClearResetsSequenceCounter(): void
    {
        $pq = PriorityQueue::any();
        $pq->enqueue('a', 1);
        $pq->enqueue('b', 1);
        $pq->clear();

        $sequence = new ReflectionProperty(PriorityQueue::class, 'sequence');
        self::assertSame(0, $sequence->getValue($pq));
    }

    public function testMixedAcceptsScalars(): void
    {
        $pq = PriorityQueue::any();
        $pq->enqueue('low', 1);
        $pq->enqueue('high', 10);
        $pq->enqueue(42, 5);
        self::assertSame('high', $pq->dequeue());
        self::assertSame(42, $pq->dequeue());
        self::assertSame('low', $pq->dequeue());
    }

    /**
     * A5: current() reads $iterSnapshot which is null until rewind() runs, so
     * naive use crashes with a TypeError. Iterator contract calls this
     * "undefined", but defensive null/value handling is safer. If this test
     * passes, A5 is fixed.
     */
    public function testCurrentBeforeRewindDoesNotCrash(): void
    {
        $pq = PriorityQueue::any();
        $pq->enqueue('a', 1);
        // Should return null or the top value gracefully — not throw TypeError
        $result = $pq->current();
        self::assertTrue($result === null || $result === 'a');
    }

    public function testIsEmptyAndClear(): void
    {
        $pq = PriorityQueue::any();
        self::assertTrue($pq->isEmpty());
        $pq->enqueue('x', 1);
        $pq->enqueue('y', 2);
        self::assertFalse($pq->isEmpty());
        $pq->clear();
        self::assertTrue($pq->isEmpty());
        self::assertCount(0, $pq);
        self::assertNull($pq->peek());
        $pq->enqueue('z', 1);
        self::assertSame('z', $pq->dequeue());
    }

    public function testManyOperationsPreserveHeapInvariant(): void
    {
        $pq = PriorityQueue::any();
        $items = [];
        for ($i = 0; $i < 50; ++$i) {
            $obj = new stdClass();
            $obj->n = $i;
            $items[] = [$obj, rand(0, 100)];
            $pq->enqueue($obj, $items[$i][1]);
        }
        // Drain in non-increasing priority order
        $prev = PHP_INT_MAX;
        $drained = 0;
        while (($item = $pq->dequeue()) !== null) {
            // Look up its priority in the original input
            foreach ($items as [$obj, $prio]) {
                if ($obj === $item) {
                    self::assertLessThanOrEqual($prev, $prio);
                    $prev = $prio;

                    break;
                }
            }
            ++$drained;
        }
        self::assertSame(50, $drained);
    }
}
