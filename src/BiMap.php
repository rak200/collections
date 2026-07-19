<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;
use InvalidArgumentException;
use function count, get_debug_type, is_int, is_string, key, next, reset, var_export;

/**
 * Bidirectional map with unique keys AND unique values.
 *
 * Supports lookup in both directions: by key via `getByKey()` and by value via
 * `getByValue()`. Values can be of any type — they are hashed via the same
 * hybrid scheme used by {@see Set} (`spl_object_id` for objects, value for
 * scalars/null/arrays). `put()` rejects conflicts; use `forcePut()` to
 * overwrite any existing mapping on either side.
 *
 * Common cases: session-id ↔ user mappings, slug ↔ entity lookups, enum code
 * ↔ label tables, any one-to-one relation you want to query from either side.
 *
 * Complexity:
 * - O(1): put / forcePut / getByKey / getByValue / hasKey / hasValue / removeByKey / removeByValue / count / isEmpty / clear / getKeyType / getValueType / toArray / iteration
 *
 * @template T_Key of int|string
 * @template T_Value
 * @implements \Iterator<T_Key, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class BiMap implements \Iterator, \Countable, ToArray {

    use HashesValues;

    /** @var array<T_Key, T_Value> */
    private array $keyToValue = [];

    /** @var array<string, T_Key> Indexed by hashValue() of the value. */
    private array $valueHashToKey = [];

    /**
     * @param 'int'|'string'|'mixed' $keyType Key type to enforce.
     * @param string $valueType Class name or built-in pseudo-type to enforce on values, or `'mixed'` to skip.
     */
    protected function __construct(
        private string $keyType = 'mixed',
        private string $valueType = 'mixed'
    ) {}

    /**
     * Factory for an untyped map (no runtime type enforcement).
     *
     * @return self<int|string, mixed>
     */
    public static function any(): self {
        return new self();
    }

    /**
     * Typed factory for class-typed values. The value type is inferred
     * statically: `BiMap::of('string', Foo::class)` is `BiMap<int|string, Foo>`
     * in both PHPStan and IDE analysis. Key narrowing beyond `int|string` is
     * provided by the PHPStan extension for direct `new` calls.
     *
     * @template T of object
     * @param 'int'|'string'|'mixed' $keyType Key type to enforce.
     * @param class-string<T> $valueClass Class to enforce on values.
     * @return self<int|string, T>
     * @throws InvalidArgumentException When any key or value violates its type.
     */
    public static function of(string $keyType, string $valueClass): self {
        return new self($keyType, $valueClass);
    }

    /**
     * Configured key type. Complexity: O(1).
     * @return 'int'|'string'|'mixed'
     */
    public function getKeyType(): string {
        return $this->keyType;
    }

    /**
     * Configured value type. Complexity: O(1).
     * @return class-string<T_Value>|string
     */
    public function getValueType(): string {
        return $this->valueType;
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
            throw new InvalidArgumentException('Key must be of type int. Got: ' . get_debug_type($key));
        }
        if ($this->keyType === 'string' && !is_string($key)) {
            throw new InvalidArgumentException('Key must be of type string. Got: ' . get_debug_type($key));
        }
    }

    /**
     * Insert a key/value pair. Throws if the key or the value is already
     * mapped on either side. Use {@see forcePut()} to overwrite.
     *
     * Complexity: O(1).
     *
     * @param T_Key $key
     * @param T_Value $value
     * @throws InvalidArgumentException
     */
    public function put(int|string $key, mixed $value): void {
        $this->checkKey($key);
        ValidatesType::checkType($this->valueType, $value, 'Value');
        if (Arr::has($this->keyToValue, $key)) {
            throw new InvalidArgumentException('Key ' . var_export($key, true) . ' is already mapped.');
        }
        $valueHash = self::hashValue($value);
        if (isset($this->valueHashToKey[$valueHash])) {
            throw new InvalidArgumentException('Value is already mapped to a different key.');
        }
        $this->keyToValue[$key] = $value;
        $this->valueHashToKey[$valueHash] = $key;
    }

    /**
     * Insert a key/value pair, removing any existing mapping for either side first.
     *
     * Complexity: O(1).
     *
     * @param T_Key $key
     * @param T_Value $value
     * @throws InvalidArgumentException
     */
    public function forcePut(int|string $key, mixed $value): void {
        $this->checkKey($key);
        ValidatesType::checkType($this->valueType, $value, 'Value');
        $this->removeByKey($key);
        $this->removeByValue($value);
        $this->keyToValue[$key] = $value;
        $this->valueHashToKey[self::hashValue($value)] = $key;
    }

    /**
     * Value mapped to the given key, or null if absent. Complexity: O(1).
     *
     * @param T_Key $key
     * @return T_Value|null
     */
    public function getByKey(int|string $key): mixed {
        return $this->keyToValue[$key] ?? null;
    }

    /**
     * Key mapped to the given value, or null if absent. Complexity: O(1) reverse lookup.
     *
     * @param T_Value $value
     * @return T_Key|null
     */
    public function getByValue(mixed $value): int|string|null {
        return $this->valueHashToKey[self::hashValue($value)] ?? null;
    }

    /**
     * Whether the given key is mapped. Complexity: O(1).
     *
     * @param T_Key $key
     */
    public function hasKey(int|string $key): bool {
        return Arr::has($this->keyToValue, $key);
    }

    /**
     * Whether the given value is mapped. Complexity: O(1).
     *
     * @param T_Value $value
     */
    public function hasValue(mixed $value): bool {
        return isset($this->valueHashToKey[self::hashValue($value)]);
    }

    /**
     * Remove the entry by key. Returns true if it was present, false otherwise. Complexity: O(1).
     *
     * @param T_Key $key
     */
    public function removeByKey(int|string $key): bool {
        if (!Arr::has($this->keyToValue, $key)) {
            return false;
        }
        $value = $this->keyToValue[$key];
        unset($this->keyToValue[$key], $this->valueHashToKey[self::hashValue($value)]);
        return true;
    }

    /**
     * Remove the entry by value. Returns true if it was present, false otherwise. Complexity: O(1).
     *
     * @param T_Value $value
     */
    public function removeByValue(mixed $value): bool {
        $valueHash = self::hashValue($value);
        if (!isset($this->valueHashToKey[$valueHash])) {
            return false;
        }
        $key = $this->valueHashToKey[$valueHash];
        unset($this->valueHashToKey[$valueHash], $this->keyToValue[$key]);
        return true;
    }

    /** Number of mappings currently stored. Complexity: O(1). */
    public function count(): int {
        return count($this->keyToValue);
    }

    /** Whether the map holds no mappings. Complexity: O(1). */
    public function isEmpty(): bool {
        return $this->keyToValue === [];
    }

    /** Discard all mappings. Complexity: O(1). */
    public function clear(): void {
        $this->keyToValue = [];
        $this->valueHashToKey = [];
    }

    /** @return T_Value|null Value at the current iteration cursor, or null past the end. Complexity: O(1). */
    public function current(): mixed {
        $key = key($this->keyToValue);
        return $key === null ? null : $this->keyToValue[$key];
    }

    /** @return T_Key|null Key at the current iteration cursor, or null past the end. Complexity: O(1). */
    public function key(): int|string|null {
        return key($this->keyToValue);
    }

    /** Advance the iteration cursor. Complexity: O(1). */
    public function next(): void {
        next($this->keyToValue);
    }

    /** Reset the iteration cursor to the first entry. Complexity: O(1). */
    public function rewind(): void {
        reset($this->keyToValue);
    }

    /** Whether the iteration cursor still points at a valid entry. Complexity: O(1). */
    public function valid(): bool {
        return key($this->keyToValue) !== null;
    }

    /** @return array<T_Key, T_Value> Entries indexed by key, in insertion order. Complexity: O(1) (returned directly; PHP copies on write). */
    public function toArray(): array {
        return $this->keyToValue;
    }
}
