<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use Closure;
use PHPUnit\Framework\TestCase;
use Rak200\Collections\BiMap;
use Rak200\Collections\CircularBuffer;
use Rak200\Collections\Map;
use Rak200\Collections\MultiMap;
use Rak200\Collections\MultiSet;
use Rak200\Collections\ObjectMap;
use Rak200\Collections\OrderedSet;
use Rak200\Collections\PriorityQueue;
use Rak200\Collections\Set;
use Rak200\Collections\Stack;
use Rak200\Collections\Vector;

/**
 * Every collection's constructor is `protected` rather than `private` — see
 * CLAUDE.md "Construction — factories, not `new`". Protected (not private) is
 * load-bearing: it is what lets a consumer subclass a collection and still
 * call `parent::__construct()`, which `private` would break.
 *
 * Each case below actually instantiates an anonymous subclass that calls
 * `parent::__construct()` — a pure Reflection visibility check does not
 * execute the constructor line, so it never gets selected by Infection's
 * coverage-based test filter and the mutant escapes. Calling through a real
 * subclass exercises the line for real: it works when the constructor is
 * `protected` and raises `Error: Call to private method` when it is not.
 *
 * @internal
 *
 * @coversNothing
 */
final class ConstructorVisibilityTest extends TestCase
{
    public function testBiMapConstructorIsCallableFromSubclass(): void
    {
        $m = new class extends BiMap {
            public function __construct()
            {
                parent::__construct();
            }
        };
        self::assertInstanceOf(BiMap::class, $m);
    }

    public function testCircularBufferConstructorIsCallableFromSubclass(): void
    {
        $b = new class extends CircularBuffer {
            public function __construct()
            {
                parent::__construct(1);
            }
        };
        self::assertInstanceOf(CircularBuffer::class, $b);
    }

    public function testMapConstructorIsCallableFromSubclass(): void
    {
        $m = new class extends Map {
            public function __construct()
            {
                parent::__construct();
            }
        };
        self::assertInstanceOf(Map::class, $m);
    }

    public function testMultiMapConstructorIsCallableFromSubclass(): void
    {
        $m = new class extends MultiMap {
            public function __construct()
            {
                parent::__construct();
            }
        };
        self::assertInstanceOf(MultiMap::class, $m);
    }

    public function testMultiSetConstructorIsCallableFromSubclass(): void
    {
        $b = new class extends MultiSet {
            public function __construct()
            {
                parent::__construct();
            }
        };
        self::assertInstanceOf(MultiSet::class, $b);
    }

    public function testObjectMapConstructorIsCallableFromSubclass(): void
    {
        $m = new class extends ObjectMap {
            public function __construct()
            {
                parent::__construct();
            }
        };
        self::assertInstanceOf(ObjectMap::class, $m);
    }

    public function testOrderedSetConstructorIsCallableFromSubclass(): void
    {
        $s = new class('mixed', [], null) extends OrderedSet {
            public function __construct(string $type = 'mixed', iterable $items = [], ?Closure $comparator = null)
            {
                parent::__construct($type, $items, $comparator);
            }
        };
        self::assertInstanceOf(OrderedSet::class, $s);
    }

    public function testPriorityQueueConstructorIsCallableFromSubclass(): void
    {
        $pq = new class extends PriorityQueue {
            public function __construct()
            {
                parent::__construct();
            }
        };
        self::assertInstanceOf(PriorityQueue::class, $pq);
    }

    public function testSetConstructorIsCallableFromSubclass(): void
    {
        $s = new class('mixed', []) extends Set {
            public function __construct(string $type = 'mixed', iterable $items = [])
            {
                parent::__construct($type, $items);
            }
        };
        self::assertInstanceOf(Set::class, $s);
    }

    public function testStackConstructorIsCallableFromSubclass(): void
    {
        $s = new class extends Stack {
            public function __construct()
            {
                parent::__construct();
            }
        };
        self::assertInstanceOf(Stack::class, $s);
    }

    public function testVectorConstructorIsCallableFromSubclass(): void
    {
        $v = new class extends Vector {
            public function __construct()
            {
                parent::__construct();
            }
        };
        self::assertInstanceOf(Vector::class, $v);
    }
}
