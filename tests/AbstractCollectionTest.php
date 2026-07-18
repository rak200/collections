<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use PHPUnit\Framework\TestCase;
use Rak200\Collections\AbstractCollection;
use Rak200\Caster\Contracts\ToArray;

final class AbstractCollectionTest extends TestCase {

    /**
     * Build a minimal concrete subclass exposing a setter so we can populate
     * $items directly and exercise the inherited mechanics in isolation.
     *
     * @param array<int|string, mixed> $items
     * @return AbstractCollection<mixed>
     */
    private function make(string $type = 'mixed', array $items = []): AbstractCollection {
        return new class($type, $items) extends AbstractCollection {
            /** @param array<int|string, mixed> $items */
            public function __construct(string $type, array $items) {
                parent::__construct($type);
                $this->items = $items;
            }
        };
    }

    public function testDefaultTypeIsMixed(): void {
        $c = $this->make();
        self::assertSame('mixed', $c->getType());
    }

    public function testStoresConfiguredType(): void {
        $c = $this->make(\stdClass::class);
        self::assertSame(\stdClass::class, $c->getType());
    }

    public function testCountReflectsItems(): void {
        $c = $this->make('mixed', [1, 2, 3]);
        self::assertCount(3, $c);
        self::assertSame(3, $c->count());
    }

    public function testToArrayReturnsItems(): void {
        $c = $this->make('mixed', ['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1, 'b' => 2], $c->toArray());
    }

    public function testIteratesItemsInOrderPreservingKeys(): void {
        $c = $this->make('mixed', [10 => 'a', 20 => 'b', 30 => 'c']);
        $collected = [];
        foreach ($c as $k => $v) {
            $collected[$k] = $v;
        }
        self::assertSame([10 => 'a', 20 => 'b', 30 => 'c'], $collected);
    }

    public function testIterationOnEmptyCollection(): void {
        $c = $this->make();
        $count = 0;
        foreach ($c as $_) {
            $count++;
        }
        self::assertSame(0, $count);
    }

    public function testImplementsRequiredInterfaces(): void {
        $c = $this->make();
        self::assertInstanceOf(\Iterator::class, $c);
        self::assertInstanceOf(\Countable::class, $c);
        self::assertInstanceOf(ToArray::class, $c);
    }

    public function testIsEmptyReflectsItems(): void {
        self::assertTrue($this->make()->isEmpty());
        self::assertFalse($this->make('mixed', [1])->isEmpty());
    }

    public function testClearDiscardsItems(): void {
        $c = $this->make('mixed', [1, 2, 3]);
        $c->clear();
        self::assertTrue($c->isEmpty());
        self::assertSame([], $c->toArray());
    }
}
