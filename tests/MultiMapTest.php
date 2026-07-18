<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\MultiMap;

final class MultiMapTest extends TestCase {

    use ConstructsProtected;

    public function testEmptyState(): void {
        $m = MultiMap::any();
        self::assertCount(0, $m);
        self::assertSame(0, $m->total());
        self::assertTrue($m->isEmpty());
        self::assertSame([], $m->toArray());
        self::assertSame([], $m->get('missing'));
        self::assertNull($m->getFirst('missing'));
        self::assertSame('mixed', $m->getKeyType());
        self::assertSame('mixed', $m->getValueType());
    }

    public function testAddAppendsUnderKey(): void {
        $m = MultiMap::any();
        $m->add('h', 'one');
        $m->add('h', 'two');
        $m->add('h', 'three');
        self::assertSame(['one', 'two', 'three'], $m->get('h'));
        self::assertSame('one', $m->getFirst('h'));
        self::assertSame(3, $m->countKey('h'));
        self::assertSame(1, $m->count());
        self::assertSame(3, $m->total());
    }

    public function testHasAndHasValue(): void {
        $m = MultiMap::any();
        $m->add('k', 'a');
        $m->add('k', 'b');
        self::assertTrue($m->has('k'));
        self::assertFalse($m->has('missing'));
        self::assertTrue($m->hasValue('k', 'a'));
        self::assertTrue($m->hasValue('k', 'b'));
        self::assertFalse($m->hasValue('k', 'c'));
        self::assertFalse($m->hasValue('missing', 'a'));
    }

    public function testSetReplacesAllValues(): void {
        $m = MultiMap::any();
        $m->add('k', 'a');
        $m->add('k', 'b');
        $m->set('k', ['x', 'y']);
        self::assertSame(['x', 'y'], $m->get('k'));
    }

    public function testRemoveDropsAllValuesForKey(): void {
        $m = MultiMap::any();
        $m->add('k', 'a');
        $m->add('k', 'b');
        self::assertTrue($m->remove('k'));
        self::assertFalse($m->has('k'));
        self::assertFalse($m->remove('k'));
    }

    public function testRemoveValueDropsOnlyOneOccurrence(): void {
        $m = MultiMap::any();
        $m->add('k', 'a');
        $m->add('k', 'a');
        $m->add('k', 'b');
        self::assertTrue($m->removeValue('k', 'a'));
        self::assertSame(['a', 'b'], $m->get('k'));
        self::assertFalse($m->removeValue('k', 'missing'));
    }

    public function testRemoveLastValueDropsKey(): void {
        $m = MultiMap::any();
        $m->add('k', 'only');
        $m->removeValue('k', 'only');
        self::assertFalse($m->has('k'));
    }

    public function testKeysAndValues(): void {
        $m = MultiMap::any();
        $m->add('a', 1);
        $m->add('b', 2);
        $m->add('a', 3);
        self::assertSame(['a', 'b'], $m->keys());
        self::assertSame([1, 3, 2], $m->values());
    }

    /** @return iterable<string, array{'int'|'string'|'mixed', string, int|string, mixed}> */
    public static function rejectedEntryProvider(): iterable {
        yield 'string key into int-keyed map' => ['int', 'mixed', 'nope', 'bad'];
        yield 'stdClass value into DateTimeImmutable map' => ['string', \DateTimeImmutable::class, 'k', new \stdClass()];
    }

    /** @param 'int'|'string'|'mixed' $keyType */
    #[DataProvider('rejectedEntryProvider')]
    public function testAddRejectsInvalidKeyOrValue(string $keyType, string $valueType, int|string $key, mixed $value): void {
        $m = self::build(MultiMap::class, $keyType, $valueType);
        $this->expectException(InvalidArgumentException::class);
        $m->add($key, $value);
    }

    /** @return iterable<string, array{'int'|'string'|'mixed', string, list<mixed>}> */
    public static function rejectedValueListProvider(): iterable {
        yield 'string among ints' => ['string', 'int', [1, 2, 'three']];
    }

    /**
     * @param 'int'|'string'|'mixed' $keyType
     * @param list<mixed> $values
     */
    #[DataProvider('rejectedValueListProvider')]
    public function testSetValidatesEveryValue(string $keyType, string $valueType, array $values): void {
        $m = self::build(MultiMap::class, $keyType, $valueType);
        $this->expectException(InvalidArgumentException::class);
        $m->set('k', $values);
    }

    public function testIterationYieldsOnePairPerValue(): void {
        $m = MultiMap::any();
        $m->add('a', 1);
        $m->add('a', 2);
        $m->add('b', 3);
        $out = [];
        foreach ($m as $k => $v) {
            $out[] = [$k, $v];
        }
        self::assertSame([['a', 1], ['a', 2], ['b', 3]], $out);
    }

    public function testToArrayKeepsListShape(): void {
        $m = MultiMap::any();
        $m->add('a', 1);
        $m->add('a', 2);
        $m->add('b', 3);
        self::assertSame(['a' => [1, 2], 'b' => [3]], $m->toArray());
    }

    public function testClear(): void {
        $m = MultiMap::any();
        $m->add('a', 1);
        $m->add('b', 2);
        $m->clear();
        self::assertTrue($m->isEmpty());
        self::assertSame(0, $m->total());
    }

    public function testMixedValueAcceptsScalars(): void {
        $m = MultiMap::any();
        $m->add('mix', 1);
        $m->add('mix', 'two');
        $m->add('mix', null);
        self::assertSame([1, 'two', null], $m->get('mix'));
    }
}
