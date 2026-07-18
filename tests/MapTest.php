<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\AbstractCollection;
use Rak200\Collections\Map;

final class MapTest extends TestCase {

    use ConstructsProtected;

    public function testEmptyMapState(): void {
        $m = Map::any();
        self::assertCount(0, $m);
        self::assertSame([], $m->toArray());
        self::assertSame([], $m->keys());
        self::assertSame([], $m->values());
    }

    public function testDefaultTypesAreMixed(): void {
        $m = Map::any();
        self::assertSame('mixed', $m->getKeyType());
        self::assertSame('mixed', $m->getValueType());
    }

    public function testSetGetHas(): void {
        $m = Map::any();
        $obj = new \stdClass();
        $m->set('k', $obj);
        self::assertSame($obj, $m->get('k'));
        self::assertTrue($m->has('k'));
        self::assertFalse($m->has('missing'));
        self::assertNull($m->get('missing'));
    }

    public function testRemoveReturnsBool(): void {
        $m = Map::any();
        $m->set('k', new \stdClass());
        self::assertTrue($m->remove('k'));
        self::assertFalse($m->remove('k'));
        self::assertCount(0, $m);
    }

    /** @return iterable<string, array{'int'|'string'|'mixed', string, int|string, mixed}> */
    public static function rejectedEntryProvider(): iterable {
        yield 'string key into int-keyed map' => ['int', 'mixed', 'not-int', new \stdClass()];
        yield 'int key into string-keyed map' => ['string', 'mixed', 42, new \stdClass()];
        yield 'stdClass value into DateTimeImmutable map' => ['mixed', \DateTimeImmutable::class, 'k', new \stdClass()];
    }

    /** @param 'int'|'string'|'mixed' $keyType */
    #[DataProvider('rejectedEntryProvider')]
    public function testRejectsInvalidKeyOrValue(string $keyType, string $valueType, int|string $key, mixed $value): void {
        $m = self::build(Map::class, $keyType, $valueType);
        $this->expectException(InvalidArgumentException::class);
        $m->set($key, $value);
    }

    public function testMixedKeyTypeAcceptsBoth(): void {
        $m = Map::any();
        $m->set('a', new \stdClass());
        $m->set(7, new \stdClass());
        self::assertCount(2, $m);
    }

    public function testKeysAndValuesPreserveInsertionOrder(): void {
        $m = Map::any();
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
        $m = Map::any();
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
        $m = Map::any();
        $obj = new \stdClass();
        $m['k'] = $obj;
        self::assertSame($obj, $m['k']);
        self::assertTrue(isset($m['k']));
        unset($m['k']);
        self::assertFalse(isset($m['k']));
    }

    public function testArrayAccessNullOffsetAppendsAsInt(): void {
        $m = Map::any();
        $a = new \stdClass();
        $b = new \stdClass();
        $m[] = $a;
        $m[] = $b;
        self::assertSame([0, 1], $m->keys());
    }

    public function testArrayAccessNullOffsetRespectsStringKeyType(): void {
        $m = self::build(Map::class, 'string');
        $this->expectException(InvalidArgumentException::class);
        $m[] = new \stdClass();
    }

    public function testInitialItemsValidated(): void {
        $this->expectException(InvalidArgumentException::class);
        self::build(Map::class, 'string', 'mixed', [42 => new \stdClass()]);
    }

    public function testIsAbstractCollectionAndArrayAccess(): void {
        $m = Map::any();
        self::assertInstanceOf(AbstractCollection::class, $m);
        self::assertInstanceOf(\ArrayAccess::class, $m);
    }

    public function testMixedValueAcceptsScalars(): void {
        $m = self::build(Map::class, 'string', 'mixed');
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
