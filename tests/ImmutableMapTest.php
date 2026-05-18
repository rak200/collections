<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use BadMethodCallException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\ImmutableMap;
use Rak200\Collections\Map;

final class ImmutableMapTest extends TestCase {

    public function testEmptyState(): void {
        $m = new ImmutableMap();
        self::assertCount(0, $m);
        self::assertTrue($m->isEmpty());
        self::assertSame([], $m->toArray());
        self::assertNull($m->get('missing'));
        self::assertFalse($m->has('missing'));
        self::assertSame('mixed', $m->getKeyType());
        self::assertSame('mixed', $m->getValueType());
    }

    public function testInitialEntries(): void {
        $m = new ImmutableMap('string', 'int', ['a' => 1, 'b' => 2]);
        self::assertCount(2, $m);
        self::assertSame(1, $m->get('a'));
        self::assertSame(2, $m->get('b'));
        self::assertTrue($m->has('a'));
        self::assertSame(['a', 'b'], $m->keys());
        self::assertSame([1, 2], $m->values());
    }

    public function testKeyTypeEnforcement(): void {
        $this->expectException(InvalidArgumentException::class);
        new ImmutableMap('int', 'mixed', ['not-int' => 1]);
    }

    public function testValueTypeEnforcement(): void {
        $this->expectException(InvalidArgumentException::class);
        new ImmutableMap('mixed', \DateTimeImmutable::class, ['k' => new \stdClass()]);
    }

    public function testFromMapPreservesTypes(): void {
        $map = new Map('string', \DateTimeImmutable::class);
        $dt = new \DateTimeImmutable();
        $map->set('k', $dt);
        $imm = ImmutableMap::fromMap($map);
        self::assertSame('string', $imm->getKeyType());
        self::assertSame(\DateTimeImmutable::class, $imm->getValueType());
        self::assertSame($dt, $imm->get('k'));
    }

    public function testOffsetGetAndExistsReadOnly(): void {
        $m = new ImmutableMap('mixed', 'mixed', ['a' => 1]);
        self::assertTrue(isset($m['a']));
        self::assertSame(1, $m['a']);
        self::assertNull($m['missing']);
    }

    public function testOffsetSetThrows(): void {
        $m = new ImmutableMap();
        $this->expectException(BadMethodCallException::class);
        $m['x'] = 1;
    }

    public function testOffsetUnsetThrows(): void {
        $m = new ImmutableMap('mixed', 'mixed', ['a' => 1]);
        $this->expectException(BadMethodCallException::class);
        unset($m['a']);
    }

    public function testIteration(): void {
        $m = new ImmutableMap('string', 'int', ['a' => 1, 'b' => 2, 'c' => 3]);
        $out = [];
        foreach ($m as $k => $v) {
            $out[$k] = $v;
        }
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $out);
    }

    public function testNoMutationApiExists(): void {
        $reflect = new \ReflectionClass(ImmutableMap::class);
        self::assertFalse($reflect->hasMethod('set'));
        self::assertFalse($reflect->hasMethod('remove'));
        self::assertFalse($reflect->hasMethod('delete'));
        self::assertFalse($reflect->hasMethod('clear'));
    }

    public function testMixedAcceptsScalarsAndNull(): void {
        $m = new ImmutableMap('mixed', 'mixed', ['n' => null, 'i' => 1, 's' => 'str']);
        self::assertNull($m->get('n'));
        self::assertSame(1, $m->get('i'));
        self::assertSame('str', $m->get('s'));
    }

    public function testToArrayPreservesOrder(): void {
        $m = new ImmutableMap('mixed', 'mixed', ['x' => 10, 'y' => 20]);
        self::assertSame(['x' => 10, 'y' => 20], $m->toArray());
    }
}
