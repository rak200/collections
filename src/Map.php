<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_key_exists;
use function array_key_last;
use function array_keys;
use function array_values;
use function get_debug_type;
use function is_int;
use function is_string;
use function sprintf;
use InvalidArgumentException;
use Rak200\Collections\Internal\ValidatesType;

/**
 * Ordered key-value map with separate key and value type enforcement.
 *
 * Insertion order is preserved (PHP associative array semantics). Keys can be
 * constrained to `'int'`, `'string'`, or `'mixed'`; values to a class-string
 * or `'mixed'`. The parent's `$type` field stores the value type; the key
 * type is held in `$keyType` on this class.
 *
 * @template T_Key of int|string
 * @template T_Value
 * @extends AbstractCollection<T_Value>
 * @implements \ArrayAccess<T_Key, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Map extends AbstractCollection implements \ArrayAccess {

    /**
     * @param 'int'|'string'|'mixed' $keyType Key type to enforce.
     * @param class-string<T_Value>|'mixed'|'object'|'int'|'string'|'bool'|'float'|'array'|'iterable'|'callable' $valueType Class name or built-in pseudo-type to enforce on values, or `'mixed'` to skip.
     * @param array<T_Key, T_Value> $items Initial entries.
     * @throws InvalidArgumentException When any key or value violates its type.
     */
    public function __construct(
        private string $keyType = 'mixed',
        string $valueType = 'mixed',
        array $items = []
    ) {
        parent::__construct($valueType);
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
        return $this->type;
    }

    /**
     * Validate that $key matches the configured key type.
     *
     * @param T_Key $key
     * @throws InvalidArgumentException When the key does not match $this->keyType.
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
     * Set the value for the given key, overwriting any existing entry.
     *
     * @param T_Key $key
     * @param T_Value $value
     * @throws InvalidArgumentException When the key or value violates its type.
     */
    public function set(int|string $key, mixed $value): void {
        $this->checkKey($key);
        ValidatesType::checkType($this->type, $value, 'Value');
        $this->items[$key] = $value;
    }

    /**
     * Value at the given key, or null if absent.
     *
     * @param T_Key $key
     * @return T_Value|null
     */
    public function get(int|string $key): mixed {
        return $this->items[$key] ?? null;
    }

    /**
     * Whether the given key exists in the map.
     *
     * @param T_Key $key
     */
    public function has(int|string $key): bool {
        return array_key_exists($key, $this->items);
    }

    /**
     * Remove an entry. Returns true if it was present, false otherwise.
     *
     * @param T_Key $key
     */
    public function remove(int|string $key): bool {
        if (!array_key_exists($key, $this->items)) {
            return false;
        }
        unset($this->items[$key]);
        return true;
    }

    /** @return T_Key[] Keys in insertion order. */
    public function keys(): array {
        return array_keys($this->items);
    }

    /** @return T_Value[] Values in insertion order. */
    public function values(): array {
        return array_values($this->items);
    }

    /** @return T_Value Value at the current iteration cursor. */
    public function current(): mixed {
        return parent::current();
    }

    /**
     * Whether the given key exists in the map.
     *
     * @param T_Key $offset
     */
    public function offsetExists(mixed $offset): bool {
        return isset($this->items[$offset]);
    }

    /**
     * Value at the given key, or null if absent.
     *
     * @param T_Key $offset
     * @return T_Value|null
     */
    public function offsetGet(mixed $offset): mixed {
        return $this->items[$offset] ?? null;
    }

    /**
     * Set the value at the given key. When $offset is null, the value is
     * appended with the next available int key (`count`-based, like PHP arrays).
     *
     * @param T_Key|null $offset
     * @param T_Value $value
     * @throws InvalidArgumentException When the key or value violates its type.
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        if ($offset === null) {
            $lastKey = array_key_last($this->items);
            $nextKey = is_int($lastKey) ? $lastKey + 1 : 0;
            $this->checkKey($nextKey);
            ValidatesType::checkType($this->type, $value, 'Value');
            $this->items[$nextKey] = $value;
            return;
        }
        $this->set($offset, $value);
    }

    /**
     * Remove the entry at the given key (no-op if absent).
     *
     * @param T_Key $offset
     */
    public function offsetUnset(mixed $offset): void {
        unset($this->items[$offset]);
    }
}
