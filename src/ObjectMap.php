<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Countable;
use InvalidArgumentException;
use Iterator;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;

use function count;
use function key;
use function next;
use function reset;

/**
 * Ordered map keyed by objects.
 *
 * Unlike {@see Map}, keys are objects (or instances of a configured class).
 * Identity is by {@see spl_object_id()} — two equal-but-distinct instances
 * count as different keys (same semantics as {@see Set}). Insertion order is
 * preserved.
 *
 * Does not implement {@see \ArrayAccess}: PHP array offsets are limited to
 * `int|string`, so `$map[$obj]` is not expressible. Use `set()`/`get()`
 * instead.
 *
 * Common cases: attaching metadata / audit info / cached results to existing
 * domain objects without modifying them, identity-keyed lookups (per-instance
 * state), object → object relations.
 *
 * Complexity:
 * - O(1): set / get / has / remove / count / isEmpty / clear / getKeyType / getValueType / iteration
 * - O(n): keys / values / toArray
 *
 * @template T_Key of object
 * @template T_Value of object
 *
 * @implements Iterator<T_Key, T_Value>
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class ObjectMap implements Iterator, Countable, ToArray
{
    use HashesValues;

    /** @var array<string, T_Key> Hash → original key object. */
    private array $keys = [];

    /** @var array<string, T_Value> Hash → value (parallel to). */
    private array $values = [];

    /**
     * @param string                                $keyType   key class to enforce, or 'object' to accept any object
     * @param string                                $valueType value class to enforce, or 'object' to accept any object
     * @param iterable<array{0: T_Key, 1: T_Value}> $pairs     initial entries as `[key, value]` pairs
     *
     * @throws InvalidArgumentException when any key or value violates its type
     */
    protected function __construct(
        private string $keyType = 'object',
        private string $valueType = 'object',
        iterable $pairs = []
    ) {
        foreach ($pairs as $pair) {
            $this->set($pair[0], $pair[1]);
        }
    }

    /**
     * Factory for a map accepting any objects as keys and values.
     *
     * @param iterable<array{0: object, 1: object}> $pairs initial entries as `[key, value]` pairs
     *
     * @return self<object, object>
     */
    public static function any(iterable $pairs = []): self
    {
        return new self('object', 'object', $pairs);
    }

    /**
     * Typed factory for class-typed keys and values. Both types are inferred
     * statically: `ObjectMap::of(User::class, Role::class)` is
     * `ObjectMap<User, Role>` in both PHPStan and IDE analysis.
     *
     * @template TK of object
     * @template TV of object
     *
     * @param class-string<TK>              $keyClass   key class to enforce
     * @param class-string<TV>              $valueClass value class to enforce
     * @param iterable<array{0: TK, 1: TV}> $pairs      initial entries as `[key, value]` pairs
     *
     * @return self<TK, TV>
     *
     * @throws InvalidArgumentException when any key or value violates its type
     */
    public static function of(string $keyClass, string $valueClass, iterable $pairs = []): self
    {
        return new self($keyClass, $valueClass, $pairs);
    }

    /**
     * Configured key type. Complexity: O(1).
     *
     * @return string a value class-string, or 'object' for any object
     */
    public function getKeyType(): string
    {
        return $this->keyType;
    }

    /**
     * Configured value type. Complexity: O(1).
     *
     * @return string a value class-string, or 'object' for any object
     */
    public function getValueType(): string
    {
        return $this->valueType;
    }

    /**
     * Set the value for the given key, overwriting any existing entry. Complexity: O(1).
     *
     * @param T_Key   $key
     * @param T_Value $value
     *
     * @throws InvalidArgumentException when the key or value violates its type
     */
    public function set(object $key, object $value): void
    {
        ValidatesType::checkType($this->keyType, $key, 'Key');
        ValidatesType::checkType($this->valueType, $value, 'Value');
        $hash = self::hashValue($key);
        $this->keys[$hash] = $key;
        $this->values[$hash] = $value;
    }

    /**
     * Value at the given key, or null if absent. Complexity: O(1).
     *
     * @param T_Key $key
     *
     * @return null|T_Value
     */
    public function get(object $key): ?object
    {
        return $this->values[self::hashValue($key)] ?? null;
    }

    /**
     * Whether the given key exists in the map. Complexity: O(1).
     *
     * @param T_Key $key
     */
    public function has(object $key): bool
    {
        return isset($this->keys[self::hashValue($key)]);
    }

    /**
     * Remove an entry. Returns true if it was present, false otherwise. Complexity: O(1).
     *
     * @param T_Key $key
     */
    public function remove(object $key): bool
    {
        $hash = self::hashValue($key);
        if (!isset($this->keys[$hash])) {
            return false;
        }
        unset($this->keys[$hash], $this->values[$hash]);

        return true;
    }

    /** @return list<T_Key> Keys in insertion order. Complexity: O(n). */
    public function keys(): array
    {
        return Arr::values($this->keys);
    }

    /** @return list<T_Value> Values in insertion order. Complexity: O(n). */
    public function values(): array
    {
        return Arr::values($this->values);
    }

    /** Number of entries currently stored. Complexity: O(1). */
    public function count(): int
    {
        return count($this->keys);
    }

    /** Whether the map holds no entries. Complexity: O(1). */
    public function isEmpty(): bool
    {
        return $this->keys === [];
    }

    /** Discard all entries. Complexity: O(1). */
    public function clear(): void
    {
        $this->keys = [];
        $this->values = [];
    }

    /** @return null|T_Value Value at the current iteration cursor, or null past the end. Complexity: O(1). */
    public function current(): ?object
    {
        $hash = key($this->values);

        return $hash === null ? null : $this->values[$hash];
    }

    /** @return null|T_Key Key at the current iteration cursor, or null past the end. Complexity: O(1). */
    public function key(): ?object
    {
        $hash = key($this->values);

        return $hash === null ? null : $this->keys[$hash];
    }

    /** Advance the iteration cursor. Complexity: O(1). */
    public function next(): void
    {
        next($this->values);
    }

    /** Reset the iteration cursor to the first entry. Complexity: O(1). */
    public function rewind(): void
    {
        reset($this->values);
    }

    /** Whether the iteration cursor still points at a valid entry. Complexity: O(1). */
    public function valid(): bool
    {
        return key($this->values) !== null;
    }

    /**
     * Return entries as a list of `[key, value]` pairs in insertion order.
     *
     * A plain PHP array cannot represent object keys, so pairs are used
     * instead of a key-indexed array.
     *
     * Complexity: O(n).
     *
     * @return list<array{0: T_Key, 1: T_Value}>
     */
    public function toArray(): array
    {
        $out = [];
        foreach ($this->values as $hash => $value) {
            $out[] = [$this->keys[$hash], $value];
        }

        return $out;
    }
}
