<?php

declare(strict_types=1);

namespace Rak200\Collections;

use ArrayAccess;
use InvalidArgumentException;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;
use Rak200\Utils\Type;

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
 * Complexity:
 * - O(1): set / get / has / remove / offsetGet / offsetSet / offsetExists / offsetUnset / count / isEmpty / clear / getKeyType / getValueType / toArray / iteration
 * - O(n): keys / values
 *
 * @template T_Key of int|string
 * @template T_Value
 *
 * @extends AbstractCollection<T_Value>
 *
 * @implements ArrayAccess<T_Key, T_Value>
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Map extends AbstractCollection implements ArrayAccess
{
    /**
     * @param 'int'|'mixed'|'string'   $keyType   key type to enforce
     * @param string                   $valueType class name or built-in pseudo-type to enforce on values, or `'mixed'` to skip
     * @param iterable<T_Key, T_Value> $items     initial entries
     *
     * @throws InvalidArgumentException when any key or value violates its type
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
     * @param iterable<int|string, mixed> $items initial entries
     *
     * @return self<int|string, mixed>
     */
    public static function any(iterable $items = []): self
    {
        return new self('mixed', 'mixed', $items);
    }

    /**
     * Typed factory for class-typed values. The value type is inferred
     * statically: `Map::of('string', Foo::class)` is `Map<int|string, Foo>`
     * in both PHPStan and IDE analysis. Key narrowing beyond `int|string` is
     * provided by the PHPStan extension for direct `new` calls.
     *
     * @template T of object
     *
     * @param 'int'|'mixed'|'string'  $keyType    key type to enforce
     * @param class-string<T>         $valueClass class to enforce on values
     * @param iterable<int|string, T> $items      initial entries
     *
     * @return self<int|string, T>
     *
     * @throws InvalidArgumentException when any key or value violates its type
     */
    public static function of(string $keyType, string $valueClass, iterable $items = []): self
    {
        return new self($keyType, $valueClass, $items);
    }

    /**
     * Configured key type. Complexity: O(1).
     *
     * @return 'int'|'mixed'|'string'
     */
    public function getKeyType(): string
    {
        return $this->keyType;
    }

    /**
     * Configured value type. Complexity: O(1).
     *
     * @return class-string<T_Value>|string
     */
    public function getValueType(): string
    {
        return $this->type;
    }

    /**
     * Set the value for the given key, overwriting any existing entry. Complexity: O(1).
     *
     * @param T_Key   $key
     * @param T_Value $value
     *
     * @throws InvalidArgumentException when the key or value violates its type
     */
    public function set(int|string $key, mixed $value): void
    {
        $this->checkKey($key);
        ValidatesType::checkType($this->type, $value, 'Value');
        $this->items[$key] = $value;
    }

    /**
     * Value at the given key, or null if absent. Complexity: O(1).
     *
     * @param T_Key $key
     *
     * @return null|T_Value
     */
    public function get(int|string $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    /**
     * Whether the given key exists in the map. Complexity: O(1).
     *
     * @param T_Key $key
     */
    public function has(int|string $key): bool
    {
        return Arr::hasKey($this->items, $key);
    }

    /**
     * Remove an entry. Returns true if it was present, false otherwise. Complexity: O(1).
     *
     * @param T_Key $key
     */
    public function remove(int|string $key): bool
    {
        if (!Arr::hasKey($this->items, $key)) {
            return false;
        }
        unset($this->items[$key]);

        return true;
    }

    /** @return list<int|string> Keys in insertion order. Complexity: O(n). */
    public function keys(): array
    {
        return Arr::keys($this->items);
    }

    /** @return T_Value[] Values in insertion order. Complexity: O(n). */
    public function values(): array
    {
        return Arr::values($this->items);
    }

    /** @return null|T_Value Value at the current iteration cursor, or null past the end. Complexity: O(1). */
    public function current(): mixed
    {
        return parent::current();
    }

    /**
     * Whether the given key exists in the map. Complexity: O(1).
     *
     * @param T_Key $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Value at the given key, or null if absent. Complexity: O(1).
     *
     * @param T_Key $offset
     *
     * @return null|T_Value
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * Set the value at the given key. When $offset is null, the value is
     * appended with the next available int key (`count`-based, like PHP arrays).
     *
     * Complexity: O(1).
     *
     * @param null|T_Key $offset
     * @param T_Value    $value
     *
     * @throws InvalidArgumentException when the key or value violates its type
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $lastKey = Arr::lastKeyOrNull($this->items);
            $nextKey = Type::isInt($lastKey) ? $lastKey + 1 : 0;
            $this->checkKey($nextKey);
            ValidatesType::checkType($this->type, $value, 'Value');
            $this->items[$nextKey] = $value;

            return;
        }
        $this->set($offset, $value);
    }

    /**
     * Remove the entry at the given key (no-op if absent). Complexity: O(1).
     *
     * @param T_Key $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Validate that $key matches the configured key type.
     *
     * @throws InvalidArgumentException when the key does not match $this->keyType
     */
    private function checkKey(int|string $key): void
    {
        if ($this->keyType === 'mixed') {
            // @infection-ignore-all Equivalent mutant: removing this return still
            // exits with no throw, because the two checks below can only match
            // when $this->keyType is 'int' or 'string' — mutually exclusive with
            // 'mixed' here.
            return;
        }
        if ($this->keyType === 'int' && !Type::isInt($key)) {
            throw new InvalidArgumentException('Key must be of type int. Got: ' . Type::of($key));
        }
        if ($this->keyType === 'string' && !Type::isStr($key)) {
            throw new InvalidArgumentException('Key must be of type string. Got: ' . Type::of($key));
        }
    }
}
