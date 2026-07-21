<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Countable;
use Iterator;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\ValidatesType;

use function count;
use function key;
use function next;
use function reset;

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
 * Complexity (shared operations):
 * - O(1): getType / count / isEmpty / clear / toArray / iteration
 *
 * @template T_Value
 *
 * @implements Iterator<int|string, T_Value>
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
abstract class AbstractCollection implements Iterator, Countable, ToArray
{
    /** @var array<int|string, T_Value> */
    protected array $items = [];

    /**
     * @param string $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip. See {@see ValidatesType} for the full list.
     */
    protected function __construct(protected string $type = 'mixed') {}

    /**
     * Configured type discriminator. Complexity: O(1).
     *
     * @return class-string<T_Value>|string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /** Number of items currently stored. Complexity: O(1). */
    public function count(): int
    {
        return count($this->items);
    }

    /** Whether the collection holds no items. Complexity: O(1). */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** Discard all items. Subclasses with extra state should override. Complexity: O(1). */
    public function clear(): void
    {
        $this->items = [];
    }

    /**
     * Value at the current iteration cursor, or null past the end. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function current(): mixed
    {
        $key = key($this->items);

        return $key === null ? null : $this->items[$key];
    }

    /**
     * Key at the current iteration cursor, or null past the end. Complexity: O(1).
     */
    public function key(): int|string|null
    {
        return key($this->items);
    }

    /** Advance the iteration cursor. Complexity: O(1). */
    public function next(): void
    {
        next($this->items);
    }

    /** Reset the iteration cursor to the first item. Complexity: O(1). */
    public function rewind(): void
    {
        reset($this->items);
    }

    /** Whether the iteration cursor still points at a valid item. Complexity: O(1). */
    public function valid(): bool
    {
        return key($this->items) !== null;
    }

    /**
     * The backing array. Complexity: O(1) (returned directly; PHP copies on write).
     *
     * @return array<int|string, T_Value>
     */
    public function toArray(): array
    {
        return $this->items;
    }
}
