<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_pop;
use function count;
use InvalidArgumentException;

/**
 * LIFO stack. Iteration yields elements from top (most recently pushed) to bottom.
 *
 * @template T_Value
 * @extends AbstractCollection<T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Stack extends AbstractCollection {

    private int $position = 0;

    /**
     * @param class-string<T_Value&object>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param iterable<T_Value> $items Initial items pushed in order (last becomes top).
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(string $type = 'mixed', iterable $items = []) {
        parent::__construct($type);
        foreach ($items as $item) {
            $this->push($item);
        }
    }

    /**
     * Push onto the top.
     *
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    public function push(mixed $item): void {
        $this->checkType($item);
        $this->items[] = $item;
    }

    /**
     * Remove and return the top, or null if empty.
     *
     * @return T_Value|null
     */
    public function pop(): mixed {
        return array_pop($this->items);
    }

    /**
     * Return the top without removing it, or null if empty.
     *
     * @return T_Value|null
     */
    public function peek(): mixed {
        $count = count($this->items);
        return $count === 0 ? null : $this->items[$count - 1];
    }

    /** @return T_Value */
    public function current(): mixed {
        return $this->items[count($this->items) - 1 - $this->position];
    }

    public function key(): int {
        return $this->position;
    }

    public function next(): void {
        $this->position++;
    }

    public function rewind(): void {
        $this->position = 0;
    }

    public function valid(): bool {
        return $this->position < count($this->items);
    }
}
