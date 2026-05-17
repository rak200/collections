<?php

declare(strict_types=1);

namespace Rak200\Collections;

use InvalidArgumentException;

/**
 * Typed generic collection of mixed values, indexed by int.
 *
 * Implements `Iterator`, `ArrayAccess`, `Countable`, and `ToArray` (the first,
 * third, and fourth come from {@see AbstractCollection}). When a class-string
 * is given as the type, every item must be an instance of that class; with
 * 'mixed', any value is accepted.
 *
 * @template T_Value
 * @extends AbstractCollection<T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Vector extends AbstractCollection implements \ArrayAccess {

    /**
     * @param class-string<T_Value&object>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param T_Value[] $items Initial items indexed by int.
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(string $type = 'mixed', array $items = []) {
        parent::__construct($type);
        foreach ($items as $item) {
            $this->checkType($item);
        }
        $this->items = $items;
    }

    /** @return int */
    public function key(): int {
        return parent::key();
    }

    /** @param int $offset */
    public function offsetExists(mixed $offset): bool {
        return isset($this->items[$offset]);
    }

    /**
     * @param int $offset
     * @return T_Value|null
     */
    public function offsetGet(mixed $offset): mixed {
        return $this->items[$offset] ?? null;
    }

    /**
     * @param int|null $offset
     * @param T_Value $value
     * @throws InvalidArgumentException
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        $this->checkType($value);
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /** @param int $offset */
    public function offsetUnset(mixed $offset): void {
        unset($this->items[$offset]);
    }

    /**
     * @param int $offset
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    public function add(int $offset, mixed $item): void {
        $this->checkType($item);
        $this->items[$offset] = $item;
    }

    /** @param int $offset */
    public function remove(int $offset): void {
        unset($this->items[$offset]);
    }

    /**
     * @param int $offset
     * @return T_Value|null
     */
    public function get(int $offset): mixed {
        return $this->items[$offset] ?? null;
    }
}
