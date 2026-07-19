<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function count, get_debug_type, is_int, is_string, key, next, reset, sprintf;
use BadMethodCallException;
use InvalidArgumentException;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;

/**
 * Read-only counterpart to {@see Map}. Entries are fixed at construction;
 * `offsetSet` and `offsetUnset` throw {@see BadMethodCallException} so the
 * immutability extends to array-access syntax.
 *
 * Key and value types follow the same rules as {@see Map}: `$keyType` is
 * `'int'`, `'string'`, or `'mixed'`; `$valueType` accepts class-strings,
 * built-in pseudo-types, `'object'`, or `'mixed'`.
 *
 * Common cases: frozen configuration / feature flags, lookup tables built
 * once at boot, defensive returns from getters that want to forbid caller
 * mutation, value-object property bags.
 *
 * Complexity:
 * - O(1): get / has / offsetGet / offsetExists / count / isEmpty / getKeyType / getValueType / toArray / iteration
 * - O(n): keys / values
 *
 * @template T_Key of int|string
 * @template T_Value
 * @implements \Iterator<T_Key, T_Value>
 * @implements \ArrayAccess<T_Key, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class ImmutableMap implements \Iterator, \ArrayAccess, \Countable, ToArray {

    /** @var array<T_Key, T_Value> */
    private array $items = [];

    /**
     * @param 'int'|'string'|'mixed' $keyType Key type to enforce.
     * @param string $valueType Class name or built-in pseudo-type to enforce on values, or `'mixed'` to skip.
     * @param iterable<T_Key, T_Value> $items Initial entries (final).
     * @throws InvalidArgumentException When any key or value violates its type.
     */
    protected function __construct(
        private string $keyType = 'mixed',
        private string $valueType = 'mixed',
        iterable $items = []
    ) {
        foreach ($items as $key => $value) {
            $this->checkKey($key);
            ValidatesType::checkType($this->valueType, $value, 'Value');
            $this->items[$key] = $value;
        }
    }

    /**
     * Factory for an untyped map (no runtime type enforcement).
     *
     * @param iterable<int|string, mixed> $items Initial entries (final).
     * @return self<int|string, mixed>
     */
    public static function any(iterable $items = []): self {
        return new self('mixed', 'mixed', $items);
    }

    /**
     * Typed factory for class-typed values. The value type is inferred
     * statically: `ImmutableMap::of('string', Foo::class)` is `ImmutableMap<int|string, Foo>`
     * in both PHPStan and IDE analysis. Key narrowing beyond `int|string` is
     * provided by the PHPStan extension for direct `new` calls.
     *
     * @template T of object
     * @param 'int'|'string'|'mixed' $keyType Key type to enforce.
     * @param class-string<T> $valueClass Class to enforce on values.
     * @param iterable<int|string, T> $items Initial entries (final).
     * @return self<int|string, T>
     * @throws InvalidArgumentException When any key or value violates its type.
     */
    public static function of(string $keyType, string $valueClass, iterable $items = []): self {
        return new self($keyType, $valueClass, $items);
    }

    /**
     * Build an immutable copy of the given {@see Map}, preserving its types.
     *
     * @template TK of int|string
     * @template TV
     * @param Map<TK, TV> $map
     * @return self<int|string, TV>
     */
    public static function fromMap(Map $map): self {
        return new self($map->getKeyType(), $map->getValueType(), $map->toArray());
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
     * @param int|string $key
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
     * Value at the given key, or null if absent. Complexity: O(1).
     *
     * @param T_Key $key
     * @return T_Value|null
     */
    public function get(int|string $key): mixed {
        return $this->items[$key] ?? null;
    }

    /**
     * Whether the given key exists in the map. Complexity: O(1).
     *
     * @param T_Key $key
     */
    public function has(int|string $key): bool {
        return Arr::has($this->items, $key);
    }

    /** @return T_Key[] Keys in insertion order. Complexity: O(n). */
    public function keys(): array {
        return Arr::keys($this->items);
    }

    /** @return T_Value[] Values in insertion order. Complexity: O(n). */
    public function values(): array {
        return Arr::values($this->items);
    }

    /** Number of entries currently stored. Complexity: O(1). */
    public function count(): int {
        return count($this->items);
    }

    /** Whether the map holds no entries. Complexity: O(1). */
    public function isEmpty(): bool {
        return $this->items === [];
    }

    /** @return T_Value|null Value at the current iteration cursor, or null past the end. Complexity: O(1). */
    public function current(): mixed {
        $key = key($this->items);
        return $key === null ? null : $this->items[$key];
    }

    /** Key at the current iteration cursor. Complexity: O(1). */
    public function key(): int|string|null {
        return key($this->items);
    }

    /** Advance the iteration cursor. Complexity: O(1). */
    public function next(): void {
        next($this->items);
    }

    /** Reset the iteration cursor to the first entry. Complexity: O(1). */
    public function rewind(): void {
        reset($this->items);
    }

    /** Whether the iteration cursor still points at a valid entry. Complexity: O(1). */
    public function valid(): bool {
        return key($this->items) !== null;
    }

    /**
     * Whether the given key exists in the map. Complexity: O(1).
     *
     * @param T_Key $offset
     */
    public function offsetExists(mixed $offset): bool {
        return isset($this->items[$offset]);
    }

    /**
     * Value at the given key, or null if absent. Complexity: O(1).
     *
     * @param T_Key $offset
     * @return T_Value|null
     */
    public function offsetGet(mixed $offset): mixed {
        return $this->items[$offset] ?? null;
    }

    /**
     * Always throws — the map is immutable. Complexity: O(1).
     *
     * @throws BadMethodCallException
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        throw new BadMethodCallException('ImmutableMap cannot be modified.');
    }

    /**
     * Always throws — the map is immutable. Complexity: O(1).
     *
     * @throws BadMethodCallException
     */
    public function offsetUnset(mixed $offset): void {
        throw new BadMethodCallException('ImmutableMap cannot be modified.');
    }

    /** @return array<T_Key, T_Value> Entries in insertion order. Complexity: O(1) (returned directly; PHP copies on write). */
    public function toArray(): array {
        return $this->items;
    }
}
