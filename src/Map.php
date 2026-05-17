<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use InvalidArgumentException;

/**
 * Ordered key-value map with separate key and value type enforcement.
 *
 * Insertion order is preserved (PHP associative array semantics). Keys can be
 * constrained to `'int'`, `'string'`, or `'mixed'`; values to a class-string
 * or `'mixed'`.
 *
 * @template T_Key of int|string
 * @template T_Value of object
 * @implements \Iterator<T_Key, T_Value>
 * @implements \ArrayAccess<T_Key, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Map implements \Iterator, \ArrayAccess, \Countable, ToArray {

    /** @var array<T_Key, T_Value> */
    private array $items = [];

    /**
     * @param 'int'|'string'|'mixed' $keyType Key type to enforce.
     * @param class-string<T_Value>|'mixed' $valueType Value class to enforce, or 'mixed' to skip.
     * @param array<T_Key, T_Value> $items Initial entries.
     * @throws InvalidArgumentException When any key or value violates its type.
     */
    public function __construct(
        private string $keyType = 'mixed',
        private string $valueType = 'mixed',
        array $items = []
    ) {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /** @return 'int'|'string'|'mixed' */
    public function getKeyType(): string {
        return $this->keyType;
    }

    /** @return class-string<T_Value>|string */
    public function getValueType(): string {
        return $this->valueType;
    }

    /**
     * @param T_Key $key
     * @throws InvalidArgumentException
     */
    private function checkKey(int|string $key): void {
        if ($this->keyType === 'mixed') {
            return;
        }
        if ($this->keyType === 'int' && !is_int($key)) {
            throw new InvalidArgumentException(sprintf(
                'Key must be of type int. Got: %s',
                get_debug_type($key)
            ));
        }
        if ($this->keyType === 'string' && !is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                'Key must be of type string. Got: %s',
                get_debug_type($key)
            ));
        }
    }

    /**
     * @param T_Value $value
     * @throws InvalidArgumentException
     */
    private function checkValue(object $value): void {
        if ($this->valueType === 'mixed') {
            return;
        }
        if (!is_a($value, $this->valueType, true)) {
            throw new InvalidArgumentException(sprintf(
                'Value must be an instance of %s. Got: %s',
                $this->valueType,
                $value::class
            ));
        }
    }

    /**
     * @param T_Key $key
     * @param T_Value $value
     * @throws InvalidArgumentException
     */
    public function set(int|string $key, object $value): void {
        $this->checkKey($key);
        $this->checkValue($value);
        $this->items[$key] = $value;
    }

    /**
     * @param T_Key $key
     * @return T_Value|null
     */
    public function get(int|string $key): ?object {
        return $this->items[$key] ?? null;
    }

    /** @param T_Key $key */
    public function has(int|string $key): bool {
        return array_key_exists($key, $this->items);
    }

    /**
     * Remove an entry. Returns true if it was present, false otherwise.
     *
     * @param T_Key $key
     */
    public function delete(int|string $key): bool {
        if (!array_key_exists($key, $this->items)) {
            return false;
        }
        unset($this->items[$key]);
        return true;
    }

    /** @return T_Key[] */
    public function keys(): array {
        return array_keys($this->items);
    }

    /** @return T_Value[] */
    public function values(): array {
        return array_values($this->items);
    }

    public function count(): int {
        return count($this->items);
    }

    /** @return T_Value */
    public function current(): object {
        return current($this->items);
    }

    /** @return T_Key */
    public function key(): int|string {
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

    /** @param T_Key $offset */
    public function offsetExists(mixed $offset): bool {
        return isset($this->items[$offset]);
    }

    /**
     * @param T_Key $offset
     * @return T_Value|null
     */
    public function offsetGet(mixed $offset): ?object {
        return $this->items[$offset] ?? null;
    }

    /**
     * @param T_Key|null $offset
     * @param T_Value $value
     * @throws InvalidArgumentException
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        if ($offset === null) {
            $lastKey = array_key_last($this->items);
            $nextKey = is_int($lastKey) ? $lastKey + 1 : 0;
            $this->checkKey($nextKey);
            $this->checkValue($value);
            $this->items[] = $value;
            return;
        }
        $this->set($offset, $value);
    }

    /** @param T_Key $offset */
    public function offsetUnset(mixed $offset): void {
        unset($this->items[$offset]);
    }

    /** @return array<T_Key, T_Value> */
    public function toArray(): array {
        return $this->items;
    }
}
