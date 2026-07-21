<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use BadMethodCallException;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\ImmutableMap;
use Rak200\Collections\Map;
use ReflectionClass;
use stdClass;

/**
 * @internal
 *
 * @coversNothing
 */
final class ImmutableMapTest extends TestCase
{
    use ConstructsProtected;

    public function testEmptyState(): void
    {
        $m = ImmutableMap::any();
        self::assertCount(0, $m);
        self::assertTrue($m->isEmpty());
        self::assertSame([], $m->toArray());
        self::assertNull($m->get('missing'));
        self::assertFalse($m->has('missing'));
        self::assertSame('mixed', $m->getKeyType());
        self::assertSame('mixed', $m->getValueType());
    }

    public function testInitialEntries(): void
    {
        $m = self::build(ImmutableMap::class, 'string', 'int', ['a' => 1, 'b' => 2]);
        self::assertCount(2, $m);
        self::assertSame(1, $m->get('a'));
        self::assertSame(2, $m->get('b'));
        self::assertTrue($m->has('a'));
        self::assertSame(['a', 'b'], $m->keys());
        self::assertSame([1, 2], $m->values());
    }

    public function testKeyTypeEnforcement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Key must be of type int. Got: string');
        self::build(ImmutableMap::class, 'int', 'mixed', ['not-int' => 1]);
    }

    public function testStringKeyTypeRejectsIntKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Key must be of type string. Got: int');
        self::build(ImmutableMap::class, 'string', 'mixed', [42 => 1]);
    }

    public function testValueTypeEnforcement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ImmutableMap::of('mixed', DateTimeImmutable::class, ['k' => new stdClass()]);
    }

    public function testFromMapPreservesTypes(): void
    {
        $map = Map::of('string', DateTimeImmutable::class);
        $dt = new DateTimeImmutable();
        $map->set('k', $dt);
        $imm = ImmutableMap::fromMap($map);
        self::assertSame('string', $imm->getKeyType());
        self::assertSame(DateTimeImmutable::class, $imm->getValueType());
        self::assertSame($dt, $imm->get('k'));
    }

    public function testOffsetGetAndExistsReadOnly(): void
    {
        $m = ImmutableMap::any(['a' => 1]);
        self::assertTrue(isset($m['a']));
        self::assertSame(1, $m['a']);
        self::assertNull($m['missing']);
    }

    public function testOffsetSetThrows(): void
    {
        $m = ImmutableMap::any();
        $this->expectException(BadMethodCallException::class);
        $m['x'] = 1;
    }

    public function testOffsetUnsetThrows(): void
    {
        $m = ImmutableMap::any(['a' => 1]);
        $this->expectException(BadMethodCallException::class);
        unset($m['a']);
    }

    public function testIteration(): void
    {
        $m = self::build(ImmutableMap::class, 'string', 'int', ['a' => 1, 'b' => 2, 'c' => 3]);
        $out = [];
        foreach ($m as $k => $v) {
            $out[$k] = $v;
        }
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $out);
    }

    public function testRewindResetsCursorAfterAdvancing(): void
    {
        $m = ImmutableMap::any(['a' => 1, 'b' => 2]);
        $m->rewind();
        $m->next();
        self::assertSame(2, $m->current());
        $m->rewind();
        self::assertSame(1, $m->current());
        self::assertSame('a', $m->key());
    }

    public function testNoMutationApiExists(): void
    {
        $reflect = new ReflectionClass(ImmutableMap::class);
        self::assertFalse($reflect->hasMethod('set'));
        self::assertFalse($reflect->hasMethod('remove'));
        self::assertFalse($reflect->hasMethod('delete'));
        self::assertFalse($reflect->hasMethod('clear'));
    }

    public function testMixedAcceptsScalarsAndNull(): void
    {
        $m = ImmutableMap::any(['n' => null, 'i' => 1, 's' => 'str']);
        self::assertNull($m->get('n'));
        self::assertSame(1, $m->get('i'));
        self::assertSame('str', $m->get('s'));
    }

    public function testToArrayPreservesOrder(): void
    {
        $m = ImmutableMap::any(['x' => 10, 'y' => 20]);
        self::assertSame(['x' => 10, 'y' => 20], $m->toArray());
    }
}
