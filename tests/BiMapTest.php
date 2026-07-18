<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\BiMap;

final class BiMapTest extends TestCase {

    use ConstructsProtected;

    public function testEmptyState(): void {
        $m = BiMap::any();
        self::assertCount(0, $m);
        self::assertSame([], $m->toArray());
        self::assertSame('mixed', $m->getKeyType());
        self::assertSame('mixed', $m->getValueType());
    }

    public function testPutAndLookupBothDirections(): void {
        $m = BiMap::any();
        $alice = new \stdClass();
        $bob = new \stdClass();
        $m->put('alice', $alice);
        $m->put('bob', $bob);
        self::assertSame($alice, $m->getByKey('alice'));
        self::assertSame('alice', $m->getByValue($alice));
        self::assertSame($bob, $m->getByKey('bob'));
        self::assertSame('bob', $m->getByValue($bob));
    }

    public function testHasKeyAndHasValue(): void {
        $m = BiMap::any();
        $v = new \stdClass();
        self::assertFalse($m->hasKey('k'));
        self::assertFalse($m->hasValue($v));
        $m->put('k', $v);
        self::assertTrue($m->hasKey('k'));
        self::assertTrue($m->hasValue($v));
    }

    public function testPutRejectsDuplicateKey(): void {
        $m = BiMap::any();
        $m->put('k', new \stdClass());
        $this->expectException(InvalidArgumentException::class);
        $m->put('k', new \stdClass());
    }

    public function testPutRejectsDuplicateValue(): void {
        $m = BiMap::any();
        $v = new \stdClass();
        $m->put('k1', $v);
        $this->expectException(InvalidArgumentException::class);
        $m->put('k2', $v);
    }

    public function testForcePutOverwritesByKey(): void {
        $m = BiMap::any();
        $v1 = new \stdClass();
        $v2 = new \stdClass();
        $m->put('k', $v1);
        $m->forcePut('k', $v2);
        self::assertSame($v2, $m->getByKey('k'));
        self::assertFalse($m->hasValue($v1));
    }

    public function testForcePutOverwritesByValue(): void {
        $m = BiMap::any();
        $v = new \stdClass();
        $m->put('old', $v);
        $m->forcePut('new', $v);
        self::assertSame('new', $m->getByValue($v));
        self::assertFalse($m->hasKey('old'));
    }

    public function testRemoveByKey(): void {
        $m = BiMap::any();
        $v = new \stdClass();
        $m->put('k', $v);
        self::assertTrue($m->removeByKey('k'));
        self::assertFalse($m->hasKey('k'));
        self::assertFalse($m->hasValue($v));
        self::assertFalse($m->removeByKey('k'));
    }

    public function testRemoveByValue(): void {
        $m = BiMap::any();
        $v = new \stdClass();
        $m->put('k', $v);
        self::assertTrue($m->removeByValue($v));
        self::assertFalse($m->hasKey('k'));
        self::assertFalse($m->hasValue($v));
        self::assertFalse($m->removeByValue($v));
    }

    public function testGetByMissingKeyReturnsNull(): void {
        $m = BiMap::any();
        self::assertNull($m->getByKey('missing'));
        self::assertNull($m->getByValue(new \stdClass()));
    }

    /** @return iterable<string, array{'int'|'string'|'mixed', string, int|string, mixed}> */
    public static function rejectedEntryProvider(): iterable {
        yield 'string key into int-keyed map' => ['int', 'mixed', 'not-int', new \stdClass()];
        yield 'stdClass value into DateTimeImmutable map' => ['mixed', \DateTimeImmutable::class, 'k', new \stdClass()];
    }

    /** @param 'int'|'string'|'mixed' $keyType */
    #[DataProvider('rejectedEntryProvider')]
    public function testPutRejectsInvalidKeyOrValue(string $keyType, string $valueType, int|string $key, mixed $value): void {
        $m = self::build(BiMap::class, $keyType, $valueType);
        $this->expectException(InvalidArgumentException::class);
        $m->put($key, $value);
    }

    public function testIteration(): void {
        $m = BiMap::any();
        $a = new \stdClass();
        $b = new \stdClass();
        $m->put('alpha', $a);
        $m->put('beta', $b);
        $out = [];
        foreach ($m as $k => $v) {
            $out[$k] = $v;
        }
        self::assertSame(['alpha' => $a, 'beta' => $b], $out);
    }

    public function testToArrayReflectsKeyToValueMapping(): void {
        $m = BiMap::any();
        $a = new \stdClass();
        $b = new \stdClass();
        $m->put('a', $a);
        $m->put('b', $b);
        self::assertSame(['a' => $a, 'b' => $b], $m->toArray());
    }

    public function testMixedValueAcceptsScalars(): void {
        $m = BiMap::any();
        $m->put('one', 1);
        $m->put('two', 'string-value');
        $m->put('three', null);
        self::assertSame(1, $m->getByKey('one'));
        self::assertSame('one', $m->getByValue(1));
        self::assertSame('two', $m->getByValue('string-value'));
        self::assertSame('three', $m->getByValue(null));
    }

    public function testScalarValueUniquenessIsByValue(): void {
        $m = BiMap::any();
        $m->put('k1', 'foo');
        $this->expectException(InvalidArgumentException::class);
        $m->put('k2', 'foo');  // same scalar value — conflict
    }

    public function testScalarValueDistinctFromSameLookingDifferentType(): void {
        $m = BiMap::any();
        $m->put('intkey', 1);
        $m->put('strkey', '1');  // distinct from int 1 thanks to type prefix in hash
        self::assertSame('intkey', $m->getByValue(1));
        self::assertSame('strkey', $m->getByValue('1'));
    }

    public function testIsEmptyAndClear(): void {
        $m = BiMap::any();
        self::assertTrue($m->isEmpty());
        $m->put('a', new \stdClass());
        $m->put('b', new \stdClass());
        self::assertFalse($m->isEmpty());
        $m->clear();
        self::assertTrue($m->isEmpty());
        self::assertCount(0, $m);
        self::assertNull($m->getByKey('a'));
        // both directions cleared — can reuse the same key without conflict
        $m->put('a', new \stdClass());
        self::assertTrue($m->hasKey('a'));
    }
}
