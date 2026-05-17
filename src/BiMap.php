<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\HashesValues;
use InvalidArgumentException;
use function array_key_exists;
use function count;
use function current;
use function get_debug_type;
use function is_int;
use function is_string;
use function key;
use function next;
use function reset;
use function sprintf;
use function var_export;

/**
 * Bidirectional map with unique keys AND unique values.
 *
 * Supports lookup in both directions: by key via `getByKey()` and by value via
 * `getByValue()`. Values can be of any type — they are hashed via the same
 * hybrid scheme used by {@see Set} (`spl_object_id` for objects, value for
 * scalars/null/arrays). `put()` rejects conflicts; use `forcePut()` to
 * overwrite any existing mapping on either side.
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
     * @param class-string<T_Value>|'mixed' $valueType Value class to enforce, or 'mixed' to skip.
     */
    public function __construct(
        private string $keyType = 'mixed',
        private string $valueType = 'mixed'
    ) {}

    /** @return 'int'|'string'|'mixed' */
    public function getKeyType(): string {
        return $this->keyType;
    }

    /** @return class-string<T_Value>|string */
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
            throw new InvalidArgumentException(sprintf('Key must be of type int. Got: %s', get_debug_type($key)));
        }
        if ($this->keyType === 'string' && !is_string($key)) {
            throw new InvalidArgumentException(sprintf('Key must be of type string. Got: %s', get_debug_type($key)));
        }
    }

    /**
     * Validate that $value matches the configured value type.
     *
     * @param T_Value $value
     * @throws InvalidArgumentException When the value does not match $this->valueType.
     */
    private function checkValue(mixed $value): void {
        if ($this->valueType === 'mixed') {
            return;
        }
        if (!($value instanceof $this->valueType)) {
            throw new InvalidArgumentException(sprintf(
                'Value must be an instance of %s. Got: %s',
                $this->valueType,
                get_debug_type($value)
            ));
        }
    }

    /**
     * Insert a key/value pair. Throws if the key or the value is already
     * mapped on either side. Use {@see forcePut()} to overwrite.
     *
     * @param T_Key $key
     * @param T_Value $value
     * @throws InvalidArgumentException
     */
    public function put(int|string $key, mixed $value): void {
        $this->checkKey($key);
        $this->checkValue($value);
        if (array_key_exists($key, $this->keyToValue)) {
            throw new InvalidArgumentException(sprintf('Key %s is already mapped.', var_export($key, true)));
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
     * @param T_Key $key
     * @param T_Value $value
     * @throws InvalidArgumentException
     */
    public function forcePut(int|string $key, mixed $value): void {
        $this->checkKey($key);
        $this->checkValue($value);
        $this->removeByKey($key);
        $this->removeByValue($value);
        $this->keyToValue[$key] = $value;
        $this->valueHashToKey[self::hashValue($value)] = $key;
    }

    /**
     * Value mapped to the given key, or null if absent.
     *
     * @param T_Key $key
     * @return T_Value|null
     */
    public function getByKey(int|string $key): mixed {
        return $this->keyToValue[$key] ?? null;
    }

    /**
     * Key mapped to the given value, or null if absent.
     *
     * @param T_Value $value
     * @return T_Key|null
     */
    public function getByValue(mixed $value): int|string|null {
        return $this->valueHashToKey[self::hashValue($value)] ?? null;
    }

    /**
     * Whether the given key is mapped.
     *
     * @param T_Key $key
     */
    public function hasKey(int|string $key): bool {
        return array_key_exists($key, $this->keyToValue);
    }

    /**
     * Whether the given value is mapped.
     *
     * @param T_Value $value
     */
    public function hasValue(mixed $value): bool {
        return isset($this->valueHashToKey[self::hashValue($value)]);
    }

    /**
     * Remove the entry by key. Returns true if it was present, false otherwise.
     *
     * @param T_Key $key
     */
    public function removeByKey(int|string $key): bool {
        if (!array_key_exists($key, $this->keyToValue)) {
            return false;
        }
        $value = $this->keyToValue[$key];
        unset($this->keyToValue[$key], $this->valueHashToKey[self::hashValue($value)]);
        return true;
    }

    /**
     * Remove the entry by value. Returns true if it was present, false otherwise.
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

    /** Number of mappings currently stored. */
    public function count(): int {
        return count($this->keyToValue);
    }

    /** Whether the map holds no mappings. */
    public function isEmpty(): bool {
        return $this->keyToValue === [];
    }

    /** Discard all mappings. */
    public function clear(): void {
        $this->keyToValue = [];
        $this->valueHashToKey = [];
    }

    /** @return T_Value Value at the current iteration cursor. */
    public function current(): mixed {
        return current($this->keyToValue);
    }

    /** @return T_Key Key at the current iteration cursor. */
    public function key(): int|string {
        return key($this->keyToValue);
    }

    /** Advance the iteration cursor. */
    public function next(): void {
        next($this->keyToValue);
    }

    /** Reset the iteration cursor to the first entry. */
    public function rewind(): void {
        reset($this->keyToValue);
    }

    /** Whether the iteration cursor still points at a valid entry. */
    public function valid(): bool {
        return key($this->keyToValue) !== null;
    }

    /** @return array<T_Key, T_Value> Entries indexed by key, in insertion order. */
    public function toArray(): array {
        return $this->keyToValue;
    }
}
