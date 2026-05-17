<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\BiMap;

final class BiMapTest extends TestCase {

    public function testEmptyState(): void {
        $m = new BiMap();
        self::assertCount(0, $m);
        self::assertSame([], $m->toArray());
        self::assertSame('mixed', $m->getKeyType());
        self::assertSame('mixed', $m->getValueType());
    }

    public function testPutAndLookupBothDirections(): void {
        $m = new BiMap();
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
        $m = new BiMap();
        $v = new \stdClass();
        self::assertFalse($m->hasKey('k'));
        self::assertFalse($m->hasValue($v));
        $m->put('k', $v);
        self::assertTrue($m->hasKey('k'));
        self::assertTrue($m->hasValue($v));
    }

    public function testPutRejectsDuplicateKey(): void {
        $m = new BiMap();
        $m->put('k', new \stdClass());
        $this->expectException(InvalidArgumentException::class);
        $m->put('k', new \stdClass());
    }

    public function testPutRejectsDuplicateValue(): void {
        $m = new BiMap();
        $v = new \stdClass();
        $m->put('k1', $v);
        $this->expectException(InvalidArgumentException::class);
        $m->put('k2', $v);
    }

    public function testForcePutOverwritesByKey(): void {
        $m = new BiMap();
        $v1 = new \stdClass();
        $v2 = new \stdClass();
        $m->put('k', $v1);
        $m->forcePut('k', $v2);
        self::assertSame($v2, $m->getByKey('k'));
        self::assertFalse($m->hasValue($v1));
    }

    public function testForcePutOverwritesByValue(): void {
        $m = new BiMap();
        $v = new \stdClass();
        $m->put('old', $v);
        $m->forcePut('new', $v);
        self::assertSame('new', $m->getByValue($v));
        self::assertFalse($m->hasKey('old'));
    }

    public function testRemoveByKey(): void {
        $m = new BiMap();
        $v = new \stdClass();
        $m->put('k', $v);
        self::assertTrue($m->removeByKey('k'));
        self::assertFalse($m->hasKey('k'));
        self::assertFalse($m->hasValue($v));
        self::assertFalse($m->removeByKey('k'));
    }

    public function testRemoveByValue(): void {
        $m = new BiMap();
        $v = new \stdClass();
        $m->put('k', $v);
        self::assertTrue($m->removeByValue($v));
        self::assertFalse($m->hasKey('k'));
        self::assertFalse($m->hasValue($v));
        self::assertFalse($m->removeByValue($v));
    }

    public function testGetByMissingKeyReturnsNull(): void {
        $m = new BiMap();
        self::assertNull($m->getByKey('missing'));
        self::assertNull($m->getByValue(new \stdClass()));
    }

    public function testKeyTypeEnforcement(): void {
        $m = new BiMap('int');
        $this->expectException(InvalidArgumentException::class);
        $m->put('not-int', new \stdClass());
    }

    public function testValueTypeEnforcement(): void {
        $m = new BiMap('mixed', \DateTimeImmutable::class);
        $this->expectException(InvalidArgumentException::class);
        $m->put('k', new \stdClass());
    }

    public function testIteration(): void {
        $m = new BiMap();
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
        $m = new BiMap();
        $a = new \stdClass();
        $b = new \stdClass();
        $m->put('a', $a);
        $m->put('b', $b);
        self::assertSame(['a' => $a, 'b' => $b], $m->toArray());
    }

    public function testMixedValueAcceptsScalars(): void {
        $m = new BiMap();
        $m->put('one', 1);
        $m->put('two', 'string-value');
        $m->put('three', null);
        self::assertSame(1, $m->getByKey('one'));
        self::assertSame('one', $m->getByValue(1));
        self::assertSame('two', $m->getByValue('string-value'));
        self::assertSame('three', $m->getByValue(null));
    }

    public function testScalarValueUniquenessIsByValue(): void {
        $m = new BiMap();
        $m->put('k1', 'foo');
        $this->expectException(InvalidArgumentException::class);
        $m->put('k2', 'foo');  // same scalar value — conflict
    }

    public function testScalarValueDistinctFromSameLookingDifferentType(): void {
        $m = new BiMap();
        $m->put('intkey', 1);
        $m->put('strkey', '1');  // distinct from int 1 thanks to type prefix in hash
        self::assertSame('intkey', $m->getByValue(1));
        self::assertSame('strkey', $m->getByValue('1'));
    }
}
