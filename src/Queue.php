<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use InvalidArgumentException;
use Rak200\Collections\Internal\ProvidesValueFactories;

/**
 * FIFO queue backed by a {@see LinkedList}.
 *
 * Common cases: background job queues, BFS frontiers, producer / consumer
 * buffers, request pipelines — anywhere "first in, first out" is the rule.
 *
 * @template T_Value
 * @implements \Iterator<int, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Queue implements \Iterator, \Countable, ToArray {

    use ProvidesValueFactories;

    /** @var LinkedList<T_Value> */
    private LinkedList $list;

    /**
     * @deprecated soft-deprecated in 0.5.0 — prefer the static factories ({@see self::of()}, {@see self::ofInt()}, {@see self::any()}, …). Stays public because this collection is composed by others; will be revisited in 1.0.0.
     *
     * @param string $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param iterable<T_Value> $items Initial items enqueued in order.
     * @throws InvalidArgumentException When any item does not satisfy $type.
     */
    public function __construct(private string $type = 'mixed', iterable $items = []) {
        $this->list = new LinkedList($type);
        foreach ($items as $item) {
            $this->enqueue($item);
        }
    }

    /**
     * Typed factory for class instances. Unlike the constructor, the item
     * type is inferred statically: `Queue::of(Foo::class)` is `Queue<Foo>`
     * in both PHPStan and IDE analysis.
     *
     * @template T of object
     * @param class-string<T> $class Class to enforce on items.
     * @param iterable<T> $items Initial items enqueued in order.
     * @return self<T>
     * @throws InvalidArgumentException When any item does not satisfy $class.
     */
    public static function of(string $class, iterable $items = []): self {
        return new self($class, $items);
    }

    /**
     * Get the configured type of this queue.
     * @return class-string<T_Value>|string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Append at the tail.
     *
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    public function enqueue(mixed $item): void {
        $this->list->push($item);
    }

    /**
     * Remove and return the head, or null if empty.
     *
     * @return T_Value|null
     */
    public function dequeue(): mixed {
        return $this->list->shift();
    }

    /**
     * Return the head without removing it, or null if empty.
     *
     * @return T_Value|null
     */
    public function peek(): mixed {
        return $this->list->head()?->value;
    }

    /** Number of items currently in the queue. */
    public function count(): int {
        return $this->list->count();
    }

    /** Whether the queue holds no items. */
    public function isEmpty(): bool {
        return $this->list->isEmpty();
    }

    /** Discard all items. */
    public function clear(): void {
        $this->list->clear();
    }

    /** @return T_Value|null Item at the current iteration cursor, or null past the end. */
    public function current(): mixed {
        return $this->list->current();
    }

    /** Zero-based offset from the head of the queue. */
    public function key(): int {
        return $this->list->key();
    }

    /** Advance the iteration cursor one step toward the tail. */
    public function next(): void {
        $this->list->next();
    }

    /** Reset the iteration cursor to the head of the queue. */
    public function rewind(): void {
        $this->list->rewind();
    }

    /** Whether the iteration cursor still points at a valid item. */
    public function valid(): bool {
        return $this->list->valid();
    }

    /** @return T_Value[] Items from head to tail. */
    public function toArray(): array {
        return $this->list->toArray();
    }
}
