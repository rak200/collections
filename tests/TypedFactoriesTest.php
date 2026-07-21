<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\BiMap;
use Rak200\Collections\CircularBuffer;
use Rak200\Collections\Deque;
use Rak200\Collections\ImmutableMap;
use Rak200\Collections\ImmutableSet;
use Rak200\Collections\LinkedList;
use Rak200\Collections\Map;
use Rak200\Collections\MultiMap;
use Rak200\Collections\MultiSet;
use Rak200\Collections\ObjectMap;
use Rak200\Collections\OrderedSet;
use Rak200\Collections\PriorityQueue;
use Rak200\Collections\Queue;
use Rak200\Collections\Set;
use Rak200\Collections\Stack;
use Rak200\Collections\Vector;
use stdClass;

/**
 * Runtime coverage of the `of()` / `ofInt()` / `any()` typed factories. Static
 * inference (the reason the factories exist) is exercised implicitly: this
 * file is analysed at PHPStan level 9, so any factory returning a wrongly
 * parameterised collection would fail the build.
 *
 * @internal
 *
 * @coversNothing
 */
final class TypedFactoriesTest extends TestCase
{
    public function testVectorFactories(): void
    {
        $v = Vector::of(stdClass::class, [new stdClass()]);
        self::assertSame(stdClass::class, $v->getType());
        self::assertInstanceOf(stdClass::class, $v->get(0));

        $ints = Vector::ofInt([1, 2]);
        self::assertSame('int', $ints->getType());
        self::assertSame([1, 2], $ints->toArray());
    }

    public function testSetFactories(): void
    {
        $s = Set::of(DateTimeImmutable::class, [new DateTimeImmutable()]);
        self::assertSame(DateTimeImmutable::class, $s->getType());
        self::assertCount(1, $s);

        $strings = Set::ofString(['a', 'a', 'b']);
        self::assertCount(2, $strings);
    }

    public function testOrderedSetFactories(): void
    {
        // Comparator ordering via of() (the only factory that carries a comparator).
        $byN = static fn (stdClass $a, stdClass $b): int => $a->n <=> $b->n;
        $three = new stdClass();
        $three->n = 3;
        $one = new stdClass();
        $one->n = 1;
        $two = new stdClass();
        $two->n = 2;
        $sorted = OrderedSet::of(stdClass::class, [$three, $one, $two], $byN);
        $ns = [];
        foreach ($sorted as $o) {
            $ns[] = $o->n;
        }
        self::assertSame([1, 2, 3], $ns);

        // Pseudo-type inference, insertion order.
        $ints = OrderedSet::ofInt([3, 1, 2]);
        self::assertSame([3, 1, 2], $ints->toArray());
    }

    public function testStackFactories(): void
    {
        $s = Stack::ofInt([1, 2]);
        self::assertSame(2, $s->pop());

        $objects = Stack::of(stdClass::class);
        $objects->push(new stdClass());
        self::assertCount(1, $objects);
    }

    public function testQueueFactories(): void
    {
        $q = Queue::ofString(['a', 'b']);
        self::assertSame('a', $q->dequeue());

        $objects = Queue::of(stdClass::class);
        $objects->enqueue(new stdClass());
        self::assertCount(1, $objects);
    }

    public function testLinkedListFactories(): void
    {
        $l = LinkedList::ofFloat([1.5, 2.5]);
        self::assertSame([1.5, 2.5], $l->toArray());

        $objects = LinkedList::of(stdClass::class);
        $objects->push(new stdClass());
        self::assertCount(1, $objects);
    }

    public function testMultiSetFactories(): void
    {
        $b = MultiSet::ofInt([1, 1, 2]);
        self::assertSame(2, $b->countOf(1));
        self::assertSame(3, $b->count());

        $objects = MultiSet::of(stdClass::class);
        $obj = new stdClass();
        $objects->add($obj, 2);
        self::assertSame(2, $objects->countOf($obj));
    }

    public function testDequeFactories(): void
    {
        $d = Deque::ofInt([1, 2, 3]);
        self::assertSame(1, $d->popFront());
        self::assertSame(3, $d->popBack());

        $objects = Deque::of(stdClass::class);
        $objects->pushBack(new stdClass());
        self::assertCount(1, $objects);
    }

    public function testPriorityQueueFactories(): void
    {
        $pq = PriorityQueue::ofString(['a', 'b']);
        self::assertSame('a', $pq->dequeue());

        $objects = PriorityQueue::of(stdClass::class);
        $objects->enqueue(new stdClass(), 5);
        self::assertCount(1, $objects);
    }

    public function testImmutableSetFactories(): void
    {
        $s = ImmutableSet::ofInt([1, 1, 2]);
        self::assertCount(2, $s);
        self::assertTrue($s->contains(1));

        $objects = ImmutableSet::of(stdClass::class, [new stdClass()]);
        self::assertCount(1, $objects);
    }

    public function testCircularBufferFactories(): void
    {
        $any = CircularBuffer::any(2, [1, 2, 3]);
        self::assertSame([2, 3], $any->toArray());
        self::assertSame(2, $any->capacity());

        $objects = CircularBuffer::of(3, stdClass::class);
        $objects->push(new stdClass());
        self::assertCount(1, $objects);
    }

    public function testMapFactories(): void
    {
        $dt = new DateTimeImmutable();
        $m = Map::of('string', DateTimeImmutable::class, ['k' => $dt]);
        self::assertSame($dt, $m->get('k'));
        self::assertSame(DateTimeImmutable::class, $m->getValueType());

        $any = Map::any(['a' => 1]);
        self::assertSame(1, $any->get('a'));
    }

    public function testImmutableMapFactories(): void
    {
        $dt = new DateTimeImmutable();
        $m = ImmutableMap::of('string', DateTimeImmutable::class, ['k' => $dt]);
        self::assertSame($dt, $m->get('k'));

        $any = ImmutableMap::any(['a' => 1]);
        self::assertSame(1, $any->get('a'));
    }

    public function testMultiMapFactories(): void
    {
        $objects = MultiMap::of('string', stdClass::class);
        $objects->add('k', new stdClass());
        self::assertSame(1, $objects->countKey('k'));

        $any = MultiMap::any();
        $any->add('a', 1);
        $any->add('a', 2);
        self::assertSame([1, 2], $any->get('a'));
    }

    public function testBiMapFactories(): void
    {
        $dt = new DateTimeImmutable();
        $m = BiMap::of('string', DateTimeImmutable::class);
        $m->put('k', $dt);
        self::assertSame($dt, $m->getByKey('k'));

        $any = BiMap::any();
        $any->put('pi', 3.14);
        self::assertSame(3.14, $any->getByKey('pi'));
        self::assertSame('pi', $any->getByValue(3.14));
    }

    public function testObjectMapFactory(): void
    {
        $key = new DateTimeImmutable();
        $value = new stdClass();
        $m = ObjectMap::of(DateTimeImmutable::class, stdClass::class, [[$key, $value]]);
        self::assertSame($value, $m->get($key));
        self::assertSame(DateTimeImmutable::class, $m->getKeyType());
        self::assertSame(stdClass::class, $m->getValueType());
    }
}
