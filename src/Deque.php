<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Countable;
use InvalidArgumentException;
use Iterator;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\ProvidesValueFactories;

/**
 * Double-ended queue. Thin facade over {@see LinkedList} that exposes
 * head/tail operations under deque vocabulary (`pushFront`/`pushBack`,
 * `popFront`/`popBack`, `peekFront`/`peekBack`).
 *
 * Useful when you want either-end semantics without the linked-list node
 * machinery — and a name that signals the intent at the call site.
 *
 * Common cases: browser-style back/forward history, sliding-window scans
 * over a stream, work-stealing queues, palindrome / two-pointer algorithms.
 *
 * Complexity:
 * - O(1): pushFront / pushBack / popFront / popBack / peekFront / peekBack / getType / count / isEmpty / clear / iteration
 * - O(n): toArray
 *
 * @template T_Value
 *
 * @implements Iterator<int, T_Value>
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Deque implements Iterator, Countable, ToArray
{
    use ProvidesValueFactories;

    /** @var LinkedList<T_Value> */
    private LinkedList $list;

    /**
     * @deprecated soft-deprecated in 0.5.0 — prefer the static factories ({@see self::of()}, {@see self::ofInt()}, {@see self::any()}, …). Stays public because this collection is composed by others; will be revisited in 1.0.0.
     *
     * @param string            $type  class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip
     * @param iterable<T_Value> $items initial items pushed to the back in order
     *
     * @throws InvalidArgumentException when any item does not satisfy $type
     */
    public function __construct(private string $type = 'mixed', iterable $items = [])
    {
        $this->list = new LinkedList($type, $items);
    }

    /**
     * Typed factory for class instances. Unlike the constructor, the item
     * type is inferred statically: `Deque::of(Foo::class)` is `Deque<Foo>`
     * in both PHPStan and IDE analysis.
     *
     * @template T of object
     *
     * @param class-string<T> $class class to enforce on items
     * @param iterable<T>     $items initial items pushed to the back in order
     *
     * @return self<T>
     *
     * @throws InvalidArgumentException when any item does not satisfy $class
     */
    public static function of(string $class, iterable $items = []): self
    {
        return new self($class, $items);
    }

    /**
     * Get the configured type of this deque. Complexity: O(1).
     *
     * @return class-string<T_Value>|string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Prepend an item at the front of the deque. Complexity: O(1).
     *
     * @param T_Value $item
     *
     * @throws InvalidArgumentException
     */
    public function pushFront(mixed $item): void
    {
        $this->list->unshift($item);
    }

    /**
     * Append an item at the back of the deque. Complexity: O(1).
     *
     * @param T_Value $item
     *
     * @throws InvalidArgumentException
     */
    public function pushBack(mixed $item): void
    {
        $this->list->push($item);
    }

    /**
     * Remove and return the front item, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function popFront(): mixed
    {
        return $this->list->shift();
    }

    /**
     * Remove and return the back item, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function popBack(): mixed
    {
        return $this->list->pop();
    }

    /**
     * Return the front item without removing it, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function peekFront(): mixed
    {
        return $this->list->head()?->value;
    }

    /**
     * Return the back item without removing it, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function peekBack(): mixed
    {
        return $this->list->tail()?->value;
    }

    /** Number of items currently in the deque. Complexity: O(1). */
    public function count(): int
    {
        return $this->list->count();
    }

    /** Whether the deque holds no items. Complexity: O(1). */
    public function isEmpty(): bool
    {
        return $this->list->isEmpty();
    }

    /** Discard all items. Complexity: O(1). */
    public function clear(): void
    {
        $this->list->clear();
    }

    /** @return null|T_Value Item at the current iteration cursor, or null past the end. Complexity: O(1). */
    public function current(): mixed
    {
        return $this->list->current();
    }

    /** Zero-based offset from the front of the deque. Complexity: O(1). */
    public function key(): int
    {
        return $this->list->key();
    }

    /** Advance the iteration cursor one step toward the back. Complexity: O(1). */
    public function next(): void
    {
        $this->list->next();
    }

    /** Reset the iteration cursor to the front of the deque. Complexity: O(1). */
    public function rewind(): void
    {
        $this->list->rewind();
    }

    /** Whether the iteration cursor still points at a valid item. Complexity: O(1). */
    public function valid(): bool
    {
        return $this->list->valid();
    }

    /** @return T_Value[] Items from front to back. Complexity: O(n). */
    public function toArray(): array
    {
        return $this->list->toArray();
    }
}
