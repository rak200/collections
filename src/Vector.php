<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function get_debug_type;
use function is_int;
use function sprintf;
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
     * @param class-string<T_Value>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param T_Value[] $items Initial items indexed by int.
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(string $type = 'mixed', array $items = []) {
        parent::__construct($type);
        foreach ($items as $key => $item) {
            if (!is_int($key)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid key type: expected int, got %s',
                    get_debug_type($key)
                ));
            }
            $this->checkType($item);
        }
        $this->items = $items;
    }

    /** Integer key at the current iteration cursor. */
    public function key(): int {
        return parent::key();
    }

    /**
     * Whether the given offset is populated.
     *
     * @param int $offset
     */
    public function offsetExists(mixed $offset): bool {
        return isset($this->items[$offset]);
    }

    /**
     * Item at the given offset, or null if absent.
     *
     * @param int $offset
     * @return T_Value|null
     */
    public function offsetGet(mixed $offset): mixed {
        return $this->items[$offset] ?? null;
    }

    /**
     * Set the item at the given offset, or append when $offset is null.
     *
     * @param int|null $offset
     * @param T_Value $value
     * @throws InvalidArgumentException When $value is not an instance of $this->type.
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        $this->checkType($value);
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Remove the item at the given offset (no-op if absent).
     *
     * @param int $offset
     */
    public function offsetUnset(mixed $offset): void {
        unset($this->items[$offset]);
    }

    /**
     * Set the item at the given offset, overwriting any existing entry.
     *
     * @param int $offset
     * @param T_Value $item
     * @throws InvalidArgumentException When $item is not an instance of $this->type.
     */
    public function add(int $offset, mixed $item): void {
        $this->checkType($item);
        $this->items[$offset] = $item;
    }

    /**
     * Remove the item at the given offset (no-op if absent).
     *
     * @param int $offset
     */
    public function remove(int $offset): void {
        unset($this->items[$offset]);
    }

    /**
     * Item at the given offset, or null if absent.
     *
     * @param int $offset
     * @return T_Value|null
     */
    public function get(int $offset): mixed {
        return $this->items[$offset] ?? null;
    }
}
