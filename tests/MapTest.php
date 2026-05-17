<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\AbstractCollection;
use Rak200\Collections\Map;

final class MapTest extends TestCase {

    public function testEmptyMapState(): void {
        $m = new Map();
        self::assertCount(0, $m);
        self::assertSame([], $m->toArray());
        self::assertSame([], $m->keys());
        self::assertSame([], $m->values());
    }

    public function testDefaultTypesAreMixed(): void {
        $m = new Map();
        self::assertSame('mixed', $m->getKeyType());
        self::assertSame('mixed', $m->getValueType());
    }

    public function testSetGetHas(): void {
        $m = new Map();
        $obj = new \stdClass();
        $m->set('k', $obj);
        self::assertSame($obj, $m->get('k'));
        self::assertTrue($m->has('k'));
        self::assertFalse($m->has('missing'));
        self::assertNull($m->get('missing'));
    }

    public function testDeleteReturnsBool(): void {
        $m = new Map();
        $m->set('k', new \stdClass());
        self::assertTrue($m->delete('k'));
        self::assertFalse($m->delete('k'));
        self::assertCount(0, $m);
    }

    public function testIntKeyTypeRejectsString(): void {
        $m = new Map('int');
        $this->expectException(InvalidArgumentException::class);
        $m->set('not-int', new \stdClass());
    }

    public function testStringKeyTypeRejectsInt(): void {
        $m = new Map('string');
        $this->expectException(InvalidArgumentException::class);
        $m->set(42, new \stdClass());
    }

    public function testMixedKeyTypeAcceptsBoth(): void {
        $m = new Map('mixed');
        $m->set('a', new \stdClass());
        $m->set(7, new \stdClass());
        self::assertCount(2, $m);
    }

    public function testValueTypeEnforcement(): void {
        $m = new Map('mixed', \DateTimeImmutable::class);
        $this->expectException(InvalidArgumentException::class);
        $m->set('k', new \stdClass());
    }

    public function testKeysAndValuesPreserveInsertionOrder(): void {
        $m = new Map();
        $a = new \stdClass();
        $b = new \stdClass();
        $c = new \stdClass();
        $m->set('one', $a);
        $m->set('two', $b);
        $m->set('three', $c);
        self::assertSame(['one', 'two', 'three'], $m->keys());
        self::assertSame([$a, $b, $c], $m->values());
    }

    public function testIterationPreservesKeysAndOrder(): void {
        $m = new Map();
        $a = new \stdClass();
        $b = new \stdClass();
        $m->set('alpha', $a);
        $m->set('beta', $b);
        $out = [];
        foreach ($m as $k => $v) {
            $out[$k] = $v;
        }
        self::assertSame(['alpha' => $a, 'beta' => $b], $out);
    }

    public function testArrayAccess(): void {
        $m = new Map();
        $obj = new \stdClass();
        $m['k'] = $obj;
        self::assertSame($obj, $m['k']);
        self::assertTrue(isset($m['k']));
        unset($m['k']);
        self::assertFalse(isset($m['k']));
    }

    public function testArrayAccessNullOffsetAppendsAsInt(): void {
        $m = new Map();
        $a = new \stdClass();
        $b = new \stdClass();
        $m[] = $a;
        $m[] = $b;
        self::assertSame([0, 1], $m->keys());
    }

    public function testArrayAccessNullOffsetRespectsStringKeyType(): void {
        $m = new Map('string');
        $this->expectException(InvalidArgumentException::class);
        $m[] = new \stdClass();
    }

    public function testInitialItemsValidated(): void {
        $this->expectException(InvalidArgumentException::class);
        new Map('string', 'mixed', [42 => new \stdClass()]);
    }

    public function testIsAbstractCollectionAndArrayAccess(): void {
        $m = new Map();
        self::assertInstanceOf(AbstractCollection::class, $m);
        self::assertInstanceOf(\ArrayAccess::class, $m);
    }

    public function testMixedValueAcceptsScalars(): void {
        $m = new Map('string', 'mixed');
        $m->set('age', 42);
        $m->set('name', 'Alice');
        $m->set('active', true);
        $m->set('nothing', null);
        self::assertSame(42, $m->get('age'));
        self::assertSame('Alice', $m->get('name'));
        self::assertTrue($m->get('active'));
        self::assertNull($m->get('nothing'));
        self::assertTrue($m->has('nothing'));
    }
}
