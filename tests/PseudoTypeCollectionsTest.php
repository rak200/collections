<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\BiMap;
use Rak200\Collections\LinkedList;
use Rak200\Collections\Map;
use Rak200\Collections\OrderedSet;
use Rak200\Collections\PriorityQueue;
use Rak200\Collections\Queue;
use Rak200\Collections\Set;
use Rak200\Collections\Stack;
use Rak200\Collections\Vector;

/**
 * Cross-collection coverage of the pseudo-type discriminators
 * (`'int'`, `'string'`, `'bool'`, `'float'`, `'array'`, `'iterable'`,
 * `'callable'`, `'object'`) exposed through {@see \Rak200\Collections\Internal\ValidatesType}.
 */
final class PseudoTypeCollectionsTest extends TestCase {

    public function testVectorOfInts(): void {
        $v = new Vector('int');
        $v->add(0, 42);
        $v->add(1, -7);
        self::assertSame(42, $v->get(0));
        self::assertSame(-7, $v->get(1));
        self::assertCount(2, $v);
    }

    public function testVectorOfIntsRejectsString(): void {
        $v = new Vector('int');
        $this->expectException(InvalidArgumentException::class);
        $v->add(0, 'not-int');
    }

    public function testVectorOfIntsRejectsFloat(): void {
        $v = new Vector('int');
        $this->expectException(InvalidArgumentException::class);
        $v->add(0, 3.14);
    }

    public function testSetOfStrings(): void {
        $s = new Set('string');
        self::assertTrue($s->add('alice'));
        self::assertTrue($s->add('bob'));
        self::assertFalse($s->add('alice')); // duplicate
        self::assertSame(['alice', 'bob'], $s->toArray());
    }

    public function testSetOfStringsRejectsInt(): void {
        $s = new Set('string');
        $this->expectException(InvalidArgumentException::class);
        $s->add(42);
    }

    public function testStackOfBools(): void {
        $s = new Stack('bool');
        $s->push(true);
        $s->push(false);
        $s->push(true);
        self::assertCount(3, $s);
        self::assertTrue($s->pop());
    }

    public function testStackOfBoolsRejectsInt(): void {
        $s = new Stack('bool');
        $this->expectException(InvalidArgumentException::class);
        $s->push(1);
    }

    public function testLinkedListOfFloats(): void {
        $l = new LinkedList('float');
        $l->push(1.5);
        $l->push(2.5);
        self::assertSame([1.5, 2.5], $l->toArray());
    }

    public function testLinkedListOfFloatsRejectsInt(): void {
        $l = new LinkedList('float');
        $this->expectException(InvalidArgumentException::class);
        $l->push(42);
    }

    public function testQueueOfArrays(): void {
        $q = new Queue('array');
        $q->enqueue([1, 2]);
        $q->enqueue(['x' => 1]);
        self::assertSame([1, 2], $q->dequeue());
        self::assertSame(['x' => 1], $q->dequeue());
    }

    public function testQueueOfArraysRejectsObject(): void {
        $q = new Queue('array');
        $this->expectException(InvalidArgumentException::class);
        $q->enqueue(new \ArrayObject([1, 2]));
    }

    public function testOrderedSetOfInts(): void {
        $o = new OrderedSet('int');
        $o->add(3);
        $o->add(1);
        $o->add(2);
        self::assertSame([3, 1, 2], $o->toArray());
    }

    public function testOrderedSetOfIntsRejectsString(): void {
        $o = new OrderedSet('int');
        $this->expectException(InvalidArgumentException::class);
        $o->add('not-int');
    }

    public function testPriorityQueueOfStrings(): void {
        $pq = new PriorityQueue('string');
        $pq->enqueue('low', 1);
        $pq->enqueue('high', 10);
        $pq->enqueue('mid', 5);
        self::assertSame('high', $pq->dequeue());
        self::assertSame('mid', $pq->dequeue());
        self::assertSame('low', $pq->dequeue());
    }

    public function testPriorityQueueOfStringsRejectsInt(): void {
        $pq = new PriorityQueue('string');
        $this->expectException(InvalidArgumentException::class);
        $pq->enqueue(42, 1);
    }

    public function testMapWithIntValues(): void {
        $m = new Map('string', 'int');
        $m->set('alice', 30);
        $m->set('bob', 25);
        self::assertSame(30, $m->get('alice'));
        self::assertSame(25, $m->get('bob'));
    }

    public function testMapWithIntValuesRejectsString(): void {
        $m = new Map('string', 'int');
        $this->expectException(InvalidArgumentException::class);
        $m->set('alice', 'thirty');
    }

    public function testBiMapWithFloatValues(): void {
        $bm = new BiMap('string', 'float');
        $bm->put('pi', 3.14159);
        $bm->put('e', 2.71828);
        self::assertSame(3.14159, $bm->getByKey('pi'));
        self::assertSame('e', $bm->getByValue(2.71828));
    }

    public function testBiMapWithFloatValuesRejectsInt(): void {
        $bm = new BiMap('string', 'float');
        $this->expectException(InvalidArgumentException::class);
        $bm->put('answer', 42);
    }

    public function testVectorOfCallables(): void {
        $v = new Vector('callable');
        $v->add(0, fn() => 'closure');
        $v->add(1, 'strlen');
        $v->add(2, [self::class, 'staticHelper']);
        self::assertCount(3, $v);
    }

    public function testVectorOfCallablesRejectsNonCallableString(): void {
        $v = new Vector('callable');
        $this->expectException(InvalidArgumentException::class);
        $v->add(0, 'not-a-real-function-xyz');
    }

    public function testStackOfIterables(): void {
        $s = new Stack('iterable');
        $s->push([1, 2]);
        $s->push(new \ArrayObject([3, 4]));
        self::assertCount(2, $s);
    }

    public function testStackOfIterablesRejectsScalar(): void {
        $s = new Stack('iterable');
        $this->expectException(InvalidArgumentException::class);
        $s->push(42);
    }

    public function testSetOfObjectsRejectsScalar(): void {
        // 'object' sentinel — any object accepted, scalars rejected
        $s = new Set('object');
        self::assertTrue($s->add(new \stdClass()));
        $this->expectException(InvalidArgumentException::class);
        $s->add('not-an-object');
    }

    public function testIntegerAliasIsAccepted(): void {
        // PHP's debug type uses 'int', but 'integer' is a valid alias.
        $v = new Vector('integer');
        $v->add(0, 42);
        self::assertSame(42, $v->get(0));
    }

    public function testBooleanAliasIsAccepted(): void {
        $v = new Vector('boolean');
        $v->add(0, true);
        self::assertTrue($v->get(0));
    }

    public function testDoubleAliasIsAccepted(): void {
        $v = new Vector('double');
        $v->add(0, 1.5);
        self::assertSame(1.5, $v->get(0));
    }

    /** Static helper exposed for the callable test above. */
    public static function staticHelper(): string {
        return 'static';
    }
}
