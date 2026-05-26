<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function count, current, key, next, reset;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\ValidatesType;

/**
 * Base class for typed collections. Provides shared mechanics:
 *
 * - `$items` storage and `$type` discriminator (both `protected` for subclasses)
 * - `getType()`, `count()`, `toArray()`
 * - default `Iterator` over the array's internal pointer
 *
 * Subclasses define their own public mutation API (push/pop, set/get/has,
 * add/remove/contains, etc.) and may additionally implement `ArrayAccess`
 * when it fits their semantics. Type validation is delegated to
 * {@see ValidatesType::checkType()}.
 *
 * @template T_Value
 * @author rak200 <rak.ricardo@windowslive.com>
 */
abstract class AbstractCollection implements \Iterator, \Countable, ToArray {

    /** @var array<int|string, T_Value> */
    protected array $items = [];

    /**
     * @param class-string<T_Value>|'mixed'|'object'|'int'|'string'|'bool'|'float'|'array'|'iterable'|'callable' $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip. See {@see ValidatesType} for the full list.
     */
    public function __construct(protected string $type = 'mixed') {}

    /** @return class-string<T_Value>|string */
    public function getType(): string {
        return $this->type;
    }

    /** Number of items currently stored. */
    public function count(): int {
        return count($this->items);
    }

    /** Whether the collection holds no items. */
    public function isEmpty(): bool {
        return $this->items === [];
    }

    /** Discard all items. Subclasses with extra state should override. */
    public function clear(): void {
        $this->items = [];
    }

    /** @return T_Value Value at the current iteration cursor. */
    public function current(): mixed {
        return current($this->items);
    }

    /** Key at the current iteration cursor. */
    public function key(): int|string {
        return key($this->items);
    }

    /** Advance the iteration cursor. */
    public function next(): void {
        next($this->items);
    }

    /** Reset the iteration cursor to the first item. */
    public function rewind(): void {
        reset($this->items);
    }

    /** Whether the iteration cursor still points at a valid item. */
    public function valid(): bool {
        return key($this->items) !== null;
    }

    /** @return array<int|string, T_Value> */
    public function toArray(): array {
        return $this->items;
    }
}
