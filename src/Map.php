<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_key_last, get_debug_type, is_int, is_string;
use InvalidArgumentException;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;

/**
 * Ordered key-value map with separate key and value type enforcement.
 *
 * Insertion order is preserved (PHP associative array semantics). Keys can be
 * constrained to `'int'`, `'string'`, or `'mixed'`; values to a class-string
 * or `'mixed'`. The parent's `$type` field stores the value type; the key
 * type is held in `$keyType` on this class.
 *
 * Common cases: keyed lookups (id → entity, slug → page, code → label),
 * in-memory indexes and caches, runtime configuration / option bags with
 * type guarantees on the values.
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
     * @param string $valueType Class name or built-in pseudo-type to enforce on values, or `'mixed'` to skip.
     * @param iterable<T_Key, T_Value> $items Initial entries.
     * @throws InvalidArgumentException When any key or value violates its type.
     */
    protected function __construct(
        private string $keyType = 'mixed',
        string $valueType = 'mixed',
        iterable $items = []
    ) {
        parent::__construct($valueType);
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Factory for an untyped map (no runtime type enforcement).
     *
     * @param iterable<int|string, mixed> $items Initial entries.
     * @return self<int|string, mixed>
     */
    public static function any(iterable $items = []): self {
        return new self('mixed', 'mixed', $items);
    }

    /**
     * Typed factory for class-typed values. The value type is inferred
     * statically: `Map::of('string', Foo::class)` is `Map<int|string, Foo>`
     * in both PHPStan and IDE analysis. Key narrowing beyond `int|string` is
     * provided by the PHPStan extension for direct `new` calls.
     *
     * @template T of object
     * @param 'int'|'string'|'mixed' $keyType Key type to enforce.
     * @param class-string<T> $valueClass Class to enforce on values.
     * @param iterable<int|string, T> $items Initial entries.
     * @return self<int|string, T>
     * @throws InvalidArgumentException When any key or value violates its type.
     */
    public static function of(string $keyType, string $valueClass, iterable $items = []): self {
        return new self($keyType, $valueClass, $items);
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
     * @param int|string $key
     * @throws InvalidArgumentException When the key does not match $this->keyType.
     */
    private function checkKey(int|string $key): void {
        if ($this->keyType === 'mixed') {
            return;
        }
        if ($this->keyType === 'int' && !is_int($key)) {
            throw new InvalidArgumentException('Key must be of type int. Got: ' . get_debug_type($key));
        }
        if ($this->keyType === 'string' && !is_string($key)) {
            throw new InvalidArgumentException('Key must be of type string. Got: ' . get_debug_type($key));
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
        return Arr::has($this->items, $key);
    }

    /**
     * Remove an entry. Returns true if it was present, false otherwise.
     *
     * @param T_Key $key
     */
    public function remove(int|string $key): bool {
        if (!Arr::has($this->items, $key)) {
            return false;
        }
        unset($this->items[$key]);
        return true;
    }

    /** @return list<int|string> Keys in insertion order. */
    public function keys(): array {
        return Arr::keys($this->items);
    }

    /** @return T_Value[] Values in insertion order. */
    public function values(): array {
        return Arr::values($this->items);
    }

    /** @return T_Value|null Value at the current iteration cursor, or null past the end. */
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
