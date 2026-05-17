<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use InvalidArgumentException;

/**
 * FIFO queue backed by a {@see LinkedList} for O(1) enqueue/dequeue.
 *
 * @template T_Value
 * @implements \Iterator<int, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Queue implements \Iterator, \Countable, ToArray {

    /** @var LinkedList<T_Value> */
    private LinkedList $list;

    /**
     * @param class-string<T_Value&object>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param iterable<T_Value> $items Initial items enqueued in order.
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(private string $type = 'mixed', iterable $items = []) {
        $this->list = new LinkedList($type);
        foreach ($items as $item) {
            $this->enqueue($item);
        }
    }

    /**
     * Get the configured type of this queue.
     * @return class-string<T_Value&object>|string
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

    public function count(): int {
        return $this->list->count();
    }

    /** @return T_Value */
    public function current(): mixed {
        return $this->list->current();
    }

    public function key(): int {
        return $this->list->key();
    }

    public function next(): void {
        $this->list->next();
    }

    public function rewind(): void {
        $this->list->rewind();
    }

    public function valid(): bool {
        return $this->list->valid();
    }

    /** @return T_Object[] */
    public function toArray(): array {
        return $this->list->toArray();
    }
}
