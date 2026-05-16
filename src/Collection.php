<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use InvalidArgumentException;

/**
 * Typed generic collection.
 *
 * Implements Iterator, ArrayAccess, Countable and ToArray. Validates that
 * every item is an instance of the configured type at runtime.
 *
 * @template T_Key of int|string
 * @template T_Object of object
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Collection implements \Iterator, \ArrayAccess, \Countable, ToArray {

    private null|int|string $position = null;

    /**
     * @param class-string<T_Object>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param T_Object[] $items Initial items indexed by T_Key.
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(private string $type = 'mixed', private array $items = []) {
        $this->checkType($items);
    }

    /**
     * @param T_Object[] $items
     * @throws InvalidArgumentException
     */
    private function checkType(array $items): void {
        if ($this->type === 'mixed') {
            return;
        }
        if (!array_all($items, fn ($item) => is_a($item, $this->type, true))) {
            throw new InvalidArgumentException(sprintf(
                'All items in the collection must be instances of %s. Invalid item found: %s',
                $this->type,
                var_export($items, true)
            ));
        }
    }

    /** @return T_Object */
    public function current(): object {
        return current($this->items);
    }

    /** @return T_Key */
    public function key(): int|string {
        return key($this->items);
    }

    public function next(): void {
        next($this->items);
        $this->position = key($this->items);
    }

    public function rewind(): void {
        reset($this->items);
        $this->position = key($this->items);
    }

    public function valid(): bool {
        return key($this->items) !== null;
    }

    /** @param T_Key $offset */
    public function offsetExists(mixed $offset): bool {
        return isset($this->items[$offset]);
    }

    /**
     * @param T_Key $offset
     * @return T_Object
     */
    public function offsetGet(mixed $offset): object {
        return $this->items[$offset] ?? null;
    }

    /**
     * @param T_Key $offset
     * @param T_Object $value
     * @throws InvalidArgumentException
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        $this->checkType([$value]);
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /** @param T_Key $offset */
    public function offsetUnset(mixed $offset): void {
        unset($this->items[$offset]);
    }

    public function count(): int {
        return count($this->items);
    }

    /**
     * @param T_Key $offset
     * @param T_Object $item
     * @throws InvalidArgumentException
     */
    public function add(int|string $offset, object $item): void {
        $this->checkType([$item]);
        $this->items[$offset] = $item;
    }

    /** @param T_Key $offset */
    public function remove(int|string $offset): void {
        unset($this->items[$offset]);
    }

    /**
     * @param T_Key $offset
     * @return ?T_Object
     */
    public function get(int|string $offset): ?object {
        return $this->items[$offset] ?? null;
    }

    /** @return T_Object[] */
    public function toArray(): array {
        return $this->items;
    }
}
