<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Countable;
use InvalidArgumentException;
use Iterator;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;
use Rak200\Utils\Type;

use function array_search;
use function array_splice;
use function count;

/**
 * Key-to-many-values map. Each key holds an ordered list of values; the same
 * key may appear repeatedly (HTTP-header style, `groupBy` results).
 *
 * Iteration yields one entry per stored value: keys with N values appear N
 * times during traversal. The snapshot is built lazily on `rewind()` and is
 * held on the instance, so nested `foreach` loops over the same map interfere
 * with each other.
 *
 * Complexity (n = distinct keys, V = total values, k = values under one key):
 * - O(1): add / get / getFirst / has / remove / countKey / count / isEmpty / clear / toArray
 * - O(k): hasValue / removeValue
 * - O(n) or O(V): set / keys / values / total / iteration (rewind snapshots every value)
 *
 * @template T_Key of int|string
 * @template T_Value
 *
 * @implements Iterator<T_Key, T_Value>
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class MultiMap implements Iterator, Countable, ToArray
{
    /** @var array<T_Key, list<T_Value>> */
    private array $items = [];

    private int $iterPos = 0;

    /** @var null|list<array{0: T_Key, 1: T_Value}> */
    private ?array $iterSnapshot = null;

    /**
     * @param 'int'|'mixed'|'string' $keyType   key type to enforce
     * @param string                 $valueType class name or built-in pseudo-type to enforce on values, or `'mixed'` to skip
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
    public static function any(): self
    {
        return new self();
    }

    /**
     * Typed factory for class-typed values. The value type is inferred
     * statically: `MultiMap::of('string', Foo::class)` is `MultiMap<int|string, Foo>`
     * in both PHPStan and IDE analysis. Key narrowing beyond `int|string` is
     * provided by the PHPStan extension for direct `new` calls.
     *
     * @template T of object
     *
     * @param 'int'|'mixed'|'string' $keyType    key type to enforce
     * @param class-string<T>        $valueClass class to enforce on values
     *
     * @return self<int|string, T>
     *
     * @throws InvalidArgumentException when any key or value violates its type
     */
    public static function of(string $keyType, string $valueClass): self
    {
        return new self($keyType, $valueClass);
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
        return $this->valueType;
    }

    /**
     * Append a value to the list for the given key. Creates the entry if absent.
     *
     * Complexity: O(1).
     *
     * @param T_Key   $key
     * @param T_Value $value
     *
     * @throws InvalidArgumentException when the key or value violates its type
     */
    public function add(int|string $key, mixed $value): void
    {
        $this->checkKey($key);
        ValidatesType::checkType($this->valueType, $value, 'Value');
        $this->items[$key][] = $value;
    }

    /**
     * Replace all values for the given key.
     *
     * Complexity: O(m), where m is the number of values supplied.
     *
     * @param T_Key         $key
     * @param list<T_Value> $values
     *
     * @throws InvalidArgumentException when the key or any value violates its type
     */
    public function set(int|string $key, array $values): void
    {
        $this->checkKey($key);
        foreach ($values as $value) {
            ValidatesType::checkType($this->valueType, $value, 'Value');
        }
        $this->items[$key] = Arr::values($values);
    }

    /**
     * All values for the given key in insertion order, or an empty list if absent.
     *
     * Complexity: O(1) (the list is returned as-is; PHP copies on write).
     *
     * @param T_Key $key
     *
     * @return list<T_Value>
     */
    public function get(int|string $key): array
    {
        return $this->items[$key] ?? [];
    }

    /**
     * First value for the given key, or null if the key has no values. Complexity: O(1).
     *
     * @param T_Key $key
     *
     * @return null|T_Value
     */
    public function getFirst(int|string $key): mixed
    {
        return $this->items[$key][0] ?? null;
    }

    /**
     * Whether the given key has any associated values. Complexity: O(1).
     *
     * @param T_Key $key
     */
    public function has(int|string $key): bool
    {
        return Arr::hasKey($this->items, $key);
    }

    /**
     * Whether the given key holds the given value (strict comparison).
     *
     * Complexity: O(k), where k is the number of values under $key
     * (linear scan of the key's list).
     *
     * @param T_Key   $key
     * @param T_Value $value
     */
    public function hasValue(int|string $key, mixed $value): bool
    {
        if (!Arr::hasKey($this->items, $key)) {
            return false;
        }

        return Arr::contains($this->items[$key], $value);
    }

    /**
     * Remove every value associated with the given key. Complexity: O(1).
     *
     * @param T_Key $key
     *
     * @return bool true if the key was present, false otherwise
     */
    public function remove(int|string $key): bool
    {
        if (!Arr::hasKey($this->items, $key)) {
            return false;
        }
        unset($this->items[$key]);

        return true;
    }

    /**
     * Remove the first occurrence of $value under $key. If the key's list
     * becomes empty, the key itself is dropped.
     *
     * Complexity: O(k), where k is the number of values under $key
     * (linear search plus a splice that re-indexes the tail).
     *
     * @param T_Key   $key
     * @param T_Value $value
     *
     * @return bool true if the value was present, false otherwise
     */
    public function removeValue(int|string $key, mixed $value): bool
    {
        if (!Arr::hasKey($this->items, $key)) {
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

    /** @return list<T_Key> Keys in insertion order (each key once). Complexity: O(n) in the number of distinct keys. */
    public function keys(): array
    {
        return Arr::keys($this->items);
    }

    /** @return list<T_Value> Flat list of every stored value in insertion order. Complexity: O(V) in the total number of values. */
    public function values(): array
    {
        $flat = [];
        foreach ($this->items as $values) {
            foreach ($values as $value) {
                $flat[] = $value;
            }
        }

        return $flat;
    }

    /**
     * Number of values associated with the given key. Complexity: O(1).
     *
     * @param T_Key $key
     */
    public function countKey(int|string $key): int
    {
        return isset($this->items[$key]) ? Arr::count($this->items[$key]) : 0;
    }

    /** Number of distinct keys currently stored. Complexity: O(1). See {@see AbstractCollection::count()} for why the native `count()` stays here. */
    public function count(): int
    {
        return count($this->items);
    }

    /** Total number of values across all keys. Complexity: O(n) in the number of distinct keys. */
    public function total(): int
    {
        $sum = 0;
        foreach ($this->items as $list) {
            $sum += Arr::count($list);
        }

        return $sum;
    }

    /** Whether the map holds no entries. Complexity: O(1). */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** Discard all entries and reset iteration state. Complexity: O(1). */
    public function clear(): void
    {
        $this->items = [];
        $this->iterSnapshot = null;
        // @infection-ignore-all Equivalent mutant: current()/key()/valid() all
        // check $iterSnapshot !== null (directly, or via `??`) before ever
        // reading $iterPos, and clear() always resets $iterSnapshot to null
        // first — so the value restarted here is unobservable until the next
        // rewind() overwrites it anyway.
        $this->iterPos = 0;
    }

    /** @return null|T_Value Value at the current iteration position, or null past the end. Complexity: O(1). */
    public function current(): mixed
    {
        return $this->iterSnapshot[$this->iterPos][1] ?? null;
    }

    /** @return T_Key Key at the current iteration position. Complexity: O(1). */
    public function key(): int|string|null
    {
        return $this->iterSnapshot[$this->iterPos][0] ?? null;
    }

    /** Advance the iteration position. Complexity: O(1). */
    public function next(): void
    {
        ++$this->iterPos;
    }

    /** Build a flattened snapshot and reset the iteration position. Complexity: O(V) in the total number of values. */
    public function rewind(): void
    {
        $this->iterSnapshot = $this->flattenSnapshot();
        $this->iterPos = 0;
    }

    /** Whether the iteration position still points at a snapshotted pair. Complexity: O(1). */
    public function valid(): bool
    {
        return $this->iterSnapshot !== null && isset($this->iterSnapshot[$this->iterPos]);
    }

    /** @return array<T_Key, list<T_Value>> Entries indexed by key, in insertion order. Complexity: O(1) (returned directly; PHP copies on write). */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Validate that $key matches the configured key type.
     *
     * @param T_Key $key
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

    /** @return list<array{0: T_Key, 1: T_Value}> */
    private function flattenSnapshot(): array
    {
        $out = [];
        foreach ($this->items as $key => $list) {
            foreach ($list as $value) {
                $out[] = [$key, $value];
            }
        }

        return $out;
    }
}
