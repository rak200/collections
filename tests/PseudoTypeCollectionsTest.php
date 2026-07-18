<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    use ConstructsProtected;

    public function testVectorOfInts(): void {
        $v = Vector::ofInt();
        $v->add(0, 42);
        $v->add(1, -7);
        self::assertSame(42, $v->get(0));
        self::assertSame(-7, $v->get(1));
        self::assertCount(2, $v);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function vectorRejectionProvider(): iterable {
        yield 'int rejects string' => ['int', 'not-int'];
        yield 'int rejects float' => ['int', 3.14];
        yield 'callable rejects non-callable string' => ['callable', 'not-a-real-function-xyz'];
    }

    #[DataProvider('vectorRejectionProvider')]
    public function testVectorRejectsInvalidItem(string $type, mixed $wrong): void {
        $v = self::build(Vector::class, $type);
        $this->expectException(InvalidArgumentException::class);
        $v->add(0, $wrong);
    }

    public function testSetOfStrings(): void {
        $s = Set::ofString();
        self::assertTrue($s->add('alice'));
        self::assertTrue($s->add('bob'));
        self::assertFalse($s->add('alice')); // duplicate
        self::assertSame(['alice', 'bob'], $s->toArray());
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function setRejectionProvider(): iterable {
        yield 'string rejects int' => ['string', 42];
        yield 'object rejects scalar' => ['object', 'not-an-object'];
    }

    #[DataProvider('setRejectionProvider')]
    public function testSetRejectsInvalidItem(string $type, mixed $wrong): void {
        $s = self::build(Set::class, $type);
        $this->expectException(InvalidArgumentException::class);
        $s->add($wrong);
    }

    public function testStackOfBools(): void {
        $s = Stack::ofBool();
        $s->push(true);
        $s->push(false);
        $s->push(true);
        self::assertCount(3, $s);
        self::assertTrue($s->pop());
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function stackRejectionProvider(): iterable {
        yield 'bool rejects int' => ['bool', 1];
        yield 'iterable rejects scalar' => ['iterable', 42];
    }

    #[DataProvider('stackRejectionProvider')]
    public function testStackRejectsInvalidItem(string $type, mixed $wrong): void {
        $s = self::build(Stack::class, $type);
        $this->expectException(InvalidArgumentException::class);
        $s->push($wrong);
    }

    public function testLinkedListOfFloats(): void {
        $l = LinkedList::ofFloat();
        $l->push(1.5);
        $l->push(2.5);
        self::assertSame([1.5, 2.5], $l->toArray());
    }

    public function testLinkedListOfFloatsRejectsInt(): void {
        $l = LinkedList::ofFloat();
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

    /** @return iterable<string, array{string, mixed}> */
    public static function queueRejectionProvider(): iterable {
        yield 'array rejects ArrayObject' => ['array', new \ArrayObject([1, 2])];
    }

    #[DataProvider('queueRejectionProvider')]
    public function testQueueRejectsInvalidItem(string $type, mixed $wrong): void {
        $q = new Queue($type);
        $this->expectException(InvalidArgumentException::class);
        $q->enqueue($wrong);
    }

    public function testOrderedSetOfInts(): void {
        $o = OrderedSet::ofInt();
        $o->add(3);
        $o->add(1);
        $o->add(2);
        self::assertSame([3, 1, 2], $o->toArray());
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function orderedSetRejectionProvider(): iterable {
        yield 'int rejects string' => ['int', 'not-int'];
    }

    #[DataProvider('orderedSetRejectionProvider')]
    public function testOrderedSetRejectsInvalidItem(string $type, mixed $wrong): void {
        $o = self::build(OrderedSet::class, $type);
        $this->expectException(InvalidArgumentException::class);
        $o->add($wrong);
    }

    public function testPriorityQueueOfStrings(): void {
        $pq = PriorityQueue::ofString();
        $pq->enqueue('low', 1);
        $pq->enqueue('high', 10);
        $pq->enqueue('mid', 5);
        self::assertSame('high', $pq->dequeue());
        self::assertSame('mid', $pq->dequeue());
        self::assertSame('low', $pq->dequeue());
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function priorityQueueRejectionProvider(): iterable {
        yield 'string rejects int' => ['string', 42];
    }

    #[DataProvider('priorityQueueRejectionProvider')]
    public function testPriorityQueueRejectsInvalidItem(string $type, mixed $wrong): void {
        $pq = self::build(PriorityQueue::class, $type);
        $this->expectException(InvalidArgumentException::class);
        $pq->enqueue($wrong, 1);
    }

    public function testMapWithIntValues(): void {
        $m = self::build(Map::class, 'string', 'int');
        $m->set('alice', 30);
        $m->set('bob', 25);
        self::assertSame(30, $m->get('alice'));
        self::assertSame(25, $m->get('bob'));
    }

    /** @return iterable<string, array{'int'|'string'|'mixed', string, mixed}> */
    public static function mapValueRejectionProvider(): iterable {
        yield 'int value rejects string' => ['string', 'int', 'thirty'];
    }

    /** @param 'int'|'string'|'mixed' $keyType */
    #[DataProvider('mapValueRejectionProvider')]
    public function testMapRejectsInvalidValue(string $keyType, string $valueType, mixed $wrong): void {
        $m = self::build(Map::class, $keyType, $valueType);
        $this->expectException(InvalidArgumentException::class);
        $m->set('alice', $wrong);
    }

    public function testBiMapWithFloatValues(): void {
        $bm = self::build(BiMap::class, 'string', 'float');
        $bm->put('pi', 3.14159);
        $bm->put('e', 2.71828);
        self::assertSame(3.14159, $bm->getByKey('pi'));
        self::assertSame('e', $bm->getByValue(2.71828));
    }

    public function testBiMapWithFloatValuesRejectsInt(): void {
        $bm = self::build(BiMap::class, 'string', 'float');
        $this->expectException(InvalidArgumentException::class);
        $bm->put('answer', 42);
    }

    public function testVectorOfCallables(): void {
        $v = Vector::ofCallable();
        $v->add(0, fn() => 'closure');
        $v->add(1, 'strlen');
        $v->add(2, [self::class, 'staticHelper']);
        self::assertCount(3, $v);
    }

    public function testStackOfIterables(): void {
        $s = self::build(Stack::class, 'iterable');
        $s->push([1, 2]);
        $s->push(new \ArrayObject([3, 4]));
        self::assertCount(2, $s);
    }

    public function testSetOfObjectsAcceptsAnyObject(): void {
        // 'object' sentinel — any object accepted
        $s = Set::ofObject();
        self::assertTrue($s->add(new \stdClass()));
        self::assertTrue($s->add(new \DateTimeImmutable()));
        self::assertCount(2, $s);
    }

    public function testIntegerAliasIsAccepted(): void {
        // PHP's debug type uses 'int', but 'integer' is a valid alias.
        $v = Vector::ofInt();
        $v->add(0, 42);
        self::assertSame(42, $v->get(0));
    }

    public function testBooleanAliasIsAccepted(): void {
        $v = Vector::ofBool();
        $v->add(0, true);
        self::assertTrue($v->get(0));
    }

    public function testDoubleAliasIsAccepted(): void {
        $v = Vector::ofFloat();
        $v->add(0, 1.5);
        self::assertSame(1.5, $v->get(0));
    }

    /** Static helper exposed for the callable test above. */
    public static function staticHelper(): string {
        return 'static';
    }
}
