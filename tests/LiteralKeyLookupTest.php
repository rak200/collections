<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Collections\BiMap;
use Rak200\Collections\ImmutableMap;
use Rak200\Collections\ImmutableSet;
use Rak200\Collections\Map;
use Rak200\Collections\MultiMap;
use Rak200\Collections\OrderedSet;
use Rak200\Collections\Set;

/**
 * Cross-cutting regression suite: every key lookup is a *literal* key lookup.
 *
 * Keys reach the backing array either straight from the caller (the map types)
 * or as an `Internal\HashesValues` hash carrying the value verbatim (`'s:a.b'`
 * for the string `'a.b'`), so a dot-aware lookup would silently traverse the
 * key instead of matching it — reporting a stored entry as absent, and letting
 * a nested array masquerade as a dotted key.
 *
 * @internal
 *
 * @coversNothing
 */
final class LiteralKeyLookupTest extends TestCase
{
    public function testMapTreatsDottedKeyLiterally(): void
    {
        $m = Map::any(['user.name' => 'ada']);
        self::assertTrue($m->has('user.name'));
        self::assertSame('ada', $m->get('user.name'));
        self::assertTrue($m->remove('user.name'));
        self::assertFalse($m->has('user.name'));
    }

    public function testMapDoesNotTraverseNestedArrayValues(): void
    {
        $m = Map::any(['user' => ['name' => 'ada']]);
        self::assertFalse($m->has('user.name'));
        self::assertFalse($m->remove('user.name'));
    }

    public function testImmutableMapTreatsDottedKeyLiterally(): void
    {
        $m = ImmutableMap::any(['user.name' => 'ada', 'user' => ['name' => 'grace']]);
        self::assertTrue($m->has('user.name'));
        self::assertSame('ada', $m->get('user.name'));
    }

    public function testMultiMapTreatsDottedKeyLiterally(): void
    {
        $m = MultiMap::any();
        $m->add('x-trace.id', 'abc');
        self::assertTrue($m->has('x-trace.id'));
        self::assertTrue($m->hasValue('x-trace.id', 'abc'));
        self::assertSame(['abc'], $m->get('x-trace.id'));
        self::assertTrue($m->removeValue('x-trace.id', 'abc'));

        $m->add('x-trace.id', 'def');
        self::assertTrue($m->remove('x-trace.id'));
        self::assertFalse($m->has('x-trace.id'));
    }

    public function testBiMapTreatsDottedKeyLiterally(): void
    {
        $b = BiMap::any();
        $b->put('user.name', 'ada');
        self::assertTrue($b->hasKey('user.name'));
        self::assertSame('ada', $b->getByKey('user.name'));
        self::assertTrue($b->removeByKey('user.name'));
        self::assertFalse($b->hasKey('user.name'));
    }

    public function testBiMapRejectsDuplicateDottedKey(): void
    {
        $b = BiMap::any();
        $b->put('user.name', 'ada');
        $this->expectExceptionMessage("Key 'user.name' is already mapped.");
        $b->put('user.name', 'grace');
    }

    public function testSetTreatsDottedStringLiterally(): void
    {
        $s = Set::any();
        self::assertTrue($s->add('a.b'));
        self::assertTrue($s->contains('a.b'));
        self::assertFalse($s->add('a.b'));
        self::assertCount(1, $s);
        self::assertTrue($s->remove('a.b'));
        self::assertFalse($s->contains('a.b'));
    }

    public function testSetKeepsDottedStringsDistinct(): void
    {
        $s = Set::any(['a.b', 'a.c', 'a']);
        self::assertCount(3, $s);
        self::assertSame(['a.b', 'a.c', 'a'], $s->toArray());
    }

    public function testOrderedSetTreatsDottedStringLiterally(): void
    {
        $s = OrderedSet::any();
        self::assertTrue($s->add('a.b'));
        self::assertTrue($s->contains('a.b'));
        self::assertFalse($s->add('a.b'));
        self::assertCount(1, $s);
    }

    public function testImmutableSetTreatsDottedStringLiterally(): void
    {
        $s = ImmutableSet::any(['a.b', 'a.b', 'a.c']);
        self::assertCount(2, $s);
        self::assertTrue($s->contains('a.b'));
        self::assertTrue($s->contains('a.c'));
        self::assertSame(['a.b', 'a.c'], $s->toArray());
    }
}
