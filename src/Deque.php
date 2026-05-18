<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use InvalidArgumentException;

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
 * @template T_Value
 * @implements \Iterator<int, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Deque implements \Iterator, \Countable, ToArray {

    /** @var LinkedList<T_Value> */
    private LinkedList $list;

    /**
     * @param class-string<T_Value>|'mixed'|'object'|'int'|'string'|'bool'|'float'|'array'|'iterable'|'callable' $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param iterable<T_Value> $items Initial items pushed to the back in order.
     * @throws InvalidArgumentException When any item does not satisfy $type.
     */
    public function __construct(private string $type = 'mixed', iterable $items = []) {
        $this->list = new LinkedList($type, $items);
    }

    /**
     * Get the configured type of this deque.
     * @return class-string<T_Value>|string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Prepend an item at the front of the deque.
     *
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    public function pushFront(mixed $item): void {
        $this->list->unshift($item);
    }

    /**
     * Append an item at the back of the deque.
     *
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    public function pushBack(mixed $item): void {
        $this->list->push($item);
    }

    /**
     * Remove and return the front item, or null if empty.
     *
     * @return T_Value|null
     */
    public function popFront(): mixed {
        return $this->list->shift();
    }

    /**
     * Remove and return the back item, or null if empty.
     *
     * @return T_Value|null
     */
    public function popBack(): mixed {
        return $this->list->pop();
    }

    /**
     * Return the front item without removing it, or null if empty.
     *
     * @return T_Value|null
     */
    public function peekFront(): mixed {
        return $this->list->head()?->value;
    }

    /**
     * Return the back item without removing it, or null if empty.
     *
     * @return T_Value|null
     */
    public function peekBack(): mixed {
        return $this->list->tail()?->value;
    }

    /** Number of items currently in the deque. */
    public function count(): int {
        return $this->list->count();
    }

    /** Whether the deque holds no items. */
    public function isEmpty(): bool {
        return $this->list->isEmpty();
    }

    /** Discard all items. */
    public function clear(): void {
        $this->list->clear();
    }

    /** @return T_Value Item at the current iteration cursor. */
    public function current(): mixed {
        return $this->list->current();
    }

    /** Zero-based offset from the front of the deque. */
    public function key(): int {
        return $this->list->key();
    }

    /** Advance the iteration cursor one step toward the back. */
    public function next(): void {
        $this->list->next();
    }

    /** Reset the iteration cursor to the front of the deque. */
    public function rewind(): void {
        $this->list->rewind();
    }

    /** Whether the iteration cursor still points at a valid item. */
    public function valid(): bool {
        return $this->list->valid();
    }

    /** @return T_Value[] Items from front to back. */
    public function toArray(): array {
        return $this->list->toArray();
    }
}
