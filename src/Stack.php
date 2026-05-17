<?php

declare(strict_types=1);

namespace Rak200\Collections;

use InvalidArgumentException;

/**
 * LIFO stack. Iteration yields elements from top (most recently pushed) to bottom.
 *
 * @template T_Object of object
 * @extends AbstractCollection<T_Object>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Stack extends AbstractCollection {

    private int $position = 0;

    /**
     * @param class-string<T_Object>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param iterable<T_Object> $items Initial items pushed in order (last becomes top).
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(string $type = 'mixed', iterable $items = []) {
        parent::__construct($type);
        foreach ($items as $item) {
            $this->push($item);
        }
    }

    /**
     * @param T_Object $item
     * @throws InvalidArgumentException
     */
    private function checkType(object $item): void {
        if ($this->type === 'mixed') {
            return;
        }
        if (!is_a($item, $this->type, true)) {
            throw new InvalidArgumentException(sprintf(
                'Item must be an instance of %s. Got: %s',
                $this->type,
                $item::class
            ));
        }
    }

    /**
     * Push onto the top.
     *
     * @param T_Object $item
     * @throws InvalidArgumentException
     */
    public function push(object $item): void {
        $this->checkType($item);
        $this->items[] = $item;
    }

    /**
     * Remove and return the top, or null if empty.
     *
     * @return T_Object|null
     */
    public function pop(): ?object {
        return array_pop($this->items);
    }

    /**
     * Return the top without removing it, or null if empty.
     *
     * @return T_Object|null
     */
    public function peek(): ?object {
        $count = count($this->items);
        return $count === 0 ? null : $this->items[$count - 1];
    }

    /** @return T_Object */
    public function current(): object {
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
