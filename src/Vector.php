<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use InvalidArgumentException;

/**
 * Typed generic collection of mixed values, indexed by int.
 *
 * Implements Iterator, ArrayAccess, Countable and ToArray. When a class-string
 * is given as the type, every item must be an instance of that class; with
 * 'mixed', any value is accepted.
 *
 * @template T_Value
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Vector implements \Iterator, \ArrayAccess, \Countable, ToArray {

    /**
     * @param class-string<T_Value&object>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param T_Value[] $items Initial items indexed by int.
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(private string $type = 'mixed', private array $items = []) {
        $this->checkType($items);
    }

    /**
     * Get the configured type of this collection.
     * @return class-string<T_Value&object>|string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @param T_Value[] $items
     * @throws InvalidArgumentException
     */
    private function checkType(array $items): void {
        if ($this->type === 'mixed') {
            return;
        }
        if (!array_all($items, fn ($item) => $item instanceof $this->type)) {
            throw new InvalidArgumentException(sprintf(
                'All items in the collection must be instances of %s. Invalid item found: %s',
                $this->type,
                var_export($items, true)
            ));
        }
    }

    /** @return T_Value */
    public function current(): mixed {
        return current($this->items);
    }

    public function key(): int {
        return key($this->items);
    }

    public function next(): void {
        next($this->items);
    }

    public function rewind(): void {
        reset($this->items);
    }

    public function valid(): bool {
        return key($this->items) !== null;
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
        $this->checkType([$value]);
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

    public function count(): int {
        return count($this->items);
    }

    /**
     * @param int $offset
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    public function add(int $offset, mixed $item): void {
        $this->checkType([$item]);
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

    /** @return T_Value[] */
    public function toArray(): array {
        return $this->items;
    }
}
