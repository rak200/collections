<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;

/**
 * Base class for typed collections. Provides shared mechanics:
 *
 * - `$items` storage and `$type` discriminator (both `protected` for subclasses)
 * - `getType()`, `count()`, `toArray()`
 * - default `Iterator` over the array's internal pointer
 *
 * Subclasses define their own public mutation API (push/pop, set/get/has,
 * add/remove/contains, etc.), their own type-check logic, and may additionally
 * implement `ArrayAccess` when it fits their semantics.
 *
 * @template T_Value
 * @author rak200 <rak.ricardo@windowslive.com>
 */
abstract class AbstractCollection implements \Iterator, \Countable, ToArray {

    /** @var array<int|string, T_Value> */
    protected array $items = [];

    /**
     * @param class-string<T_Value&object>|'mixed' $type Class name to enforce on items, or 'mixed' to skip.
     */
    public function __construct(protected string $type = 'mixed') {}

    /** @return class-string<T_Value&object>|string */
    public function getType(): string {
        return $this->type;
    }

    public function count(): int {
        return count($this->items);
    }

    /** @return T_Value */
    public function current(): mixed {
        return current($this->items);
    }

    public function key(): int|string {
        return key($this->items);
    }

    public function next(): void {
        next($this->items);
    }

    public function rewind(): void {
        reset($this->items);
    }

    public function valid(): bool {
        return key($this->items) !== null;
    }

    /** @return array<int|string, T_Value> */
    public function toArray(): array {
        return $this->items;
    }
}
