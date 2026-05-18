<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_search;
use function array_splice;
use function array_values;
use function count;
use function get_debug_type;
use function is_int;
use function is_string;
use function sprintf;
use InvalidArgumentException;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\ValidatesType;

/**
 * Key-to-many-values map. Each key holds an ordered list of values; the same
 * key may appear repeatedly (HTTP-header style, `groupBy` results).
 *
 * Iteration yields one entry per stored value: keys with N values appear N
 * times during traversal. The snapshot is built lazily on `rewind()` and is
 * held on the instance, so nested `foreach` loops over the same map interfere
 * with each other.
 *
 * @template T_Key of int|string
 * @template T_Value
 * @implements \Iterator<T_Key, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class MultiMap implements \Iterator, \Countable, ToArray {

    /** @var array<T_Key, list<T_Value>> */
    private array $items = [];

    private int $iterPos = 0;

    /** @var list<array{0: T_Key, 1: T_Value}>|null */
    private ?array $iterSnapshot = null;

    /**
     * @param 'int'|'string'|'mixed' $keyType Key type to enforce.
     * @param class-string<T_Value>|'mixed'|'object'|'int'|'string'|'bool'|'float'|'array'|'iterable'|'callable' $valueType Class name or built-in pseudo-type to enforce on values, or `'mixed'` to skip.
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
     * Append a value to the list for the given key. Creates the entry if absent.
     *
     * @param T_Key $key
     * @param T_Value $value
     * @throws InvalidArgumentException When the key or value violates its type.
     */
    public function add(int|string $key, mixed $value): void {
        $this->checkKey($key);
        ValidatesType::checkType($this->valueType, $value, 'Value');
        $this->items[$key][] = $value;
    }

    /**
     * Replace all values for the given key.
     *
     * @param T_Key $key
     * @param list<T_Value> $values
     * @throws InvalidArgumentException When the key or any value violates its type.
     */
    public function set(int|string $key, array $values): void {
        $this->checkKey($key);
        foreach ($values as $value) {
            ValidatesType::checkType($this->valueType, $value, 'Value');
        }
        $this->items[$key] = array_values($values);
    }

    /**
     * All values for the given key in insertion order, or an empty list if absent.
     *
     * @param T_Key $key
     * @return list<T_Value>
     */
    public function get(int|string $key): array {
        return $this->items[$key] ?? [];
    }

    /**
     * First value for the given key, or null if the key has no values.
     *
     * @param T_Key $key
     * @return T_Value|null
     */
    public function getFirst(int|string $key): mixed {
        return $this->items[$key][0] ?? null;
    }

    /**
     * Whether the given key has any associated values.
     *
     * @param T_Key $key
     */
    public function has(int|string $key): bool {
        return array_key_exists($key, $this->items);
    }

    /**
     * Whether the given key holds the given value (strict comparison).
     *
     * @param T_Key $key
     * @param T_Value $value
     */
    public function hasValue(int|string $key, mixed $value): bool {
        if (!array_key_exists($key, $this->items)) {
            return false;
        }
        return array_search($value, $this->items[$key], true) !== false;
    }

    /**
     * Remove every value associated with the given key.
     *
     * @param T_Key $key
     * @return bool True if the key was present, false otherwise.
     */
    public function remove(int|string $key): bool {
        if (!array_key_exists($key, $this->items)) {
            return false;
        }
        unset($this->items[$key]);
        return true;
    }

    /**
     * Remove the first occurrence of $value under $key. If the key's list
     * becomes empty, the key itself is dropped.
     *
     * @param T_Key $key
     * @param T_Value $value
     * @return bool True if the value was present, false otherwise.
     */
    public function removeValue(int|string $key, mixed $value): bool {
        if (!array_key_exists($key, $this->items)) {
            return false;
        }
        $idx = array_search($value, $this->items[$key], true);
        if ($idx === false) {
            return false;
        }
        array_splice($this->items[$key], $idx, 1);
        if ($this->items[$key] === []) {
            unset($this->items[$key]);
        }
        return true;
    }

    /** @return list<T_Key> Keys in insertion order (each key once). */
    public function keys(): array {
        return array_keys($this->items);
    }

    /** @return list<T_Value> Flat list of every stored value in insertion order. */
    public function values(): array {
        return array_merge(...array_values($this->items));
    }

    /**
     * Number of values associated with the given key.
     *
     * @param T_Key $key
     */
    public function countKey(int|string $key): int {
        return isset($this->items[$key]) ? count($this->items[$key]) : 0;
    }

    /** Number of distinct keys currently stored. */
    public function count(): int {
        return count($this->items);
    }

    /** Total number of values across all keys. */
    public function total(): int {
        $sum = 0;
        foreach ($this->items as $list) {
            $sum += count($list);
        }
        return $sum;
    }

    /** Whether the map holds no entries. */
    public function isEmpty(): bool {
        return $this->items === [];
    }

    /** Discard all entries and reset iteration state. */
    public function clear(): void {
        $this->items = [];
        $this->iterSnapshot = null;
        $this->iterPos = 0;
    }

    /** @return list<array{0: T_Key, 1: T_Value}> */
    private function flattenSnapshot(): array {
        $out = [];
        foreach ($this->items as $key => $list) {
            foreach ($list as $value) {
                $out[] = [$key, $value];
            }
        }
        return $out;
    }

    /** @return T_Value Value at the current iteration position. */
    public function current(): mixed {
        return $this->iterSnapshot[$this->iterPos][1] ?? null;
    }

    /** @return T_Key Key at the current iteration position. */
    public function key(): int|string {
        return $this->iterSnapshot[$this->iterPos][0];
    }

    /** Advance the iteration position. */
    public function next(): void {
        $this->iterPos++;
    }

    /** Build a flattened snapshot and reset the iteration position. */
    public function rewind(): void {
        $this->iterSnapshot = $this->flattenSnapshot();
        $this->iterPos = 0;
    }

    /** Whether the iteration position still points at a snapshotted pair. */
    public function valid(): bool {
        return $this->iterSnapshot !== null && isset($this->iterSnapshot[$this->iterPos]);
    }

    /** @return array<T_Key, list<T_Value>> Entries indexed by key, in insertion order. */
    public function toArray(): array {
        return $this->items;
    }
}
