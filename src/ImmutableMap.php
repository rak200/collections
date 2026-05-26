<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function count, current, get_debug_type, is_int, is_string, key, next, reset, sprintf;
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
     * @param class-string<T_Value>|'mixed'|'object'|'int'|'string'|'bool'|'float'|'array'|'iterable'|'callable' $valueType Class name or built-in pseudo-type to enforce on values, or `'mixed'` to skip.
     * @param iterable<T_Key, T_Value> $items Initial entries (final).
     * @throws InvalidArgumentException When any key or value violates its type.
     */
    public function __construct(
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
     * Build an immutable copy of the given {@see Map}, preserving its types.
     *
     * @template TK of int|string
     * @template TV
     * @param Map<TK, TV> $map
     * @return self<TK, TV>
     */
    public static function fromMap(Map $map): self {
        return new self($map->getKeyType(), $map->getValueType(), $map->toArray());
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

    /** @return T_Key[] Keys in insertion order. */
    public function keys(): array {
        return Arr::keys($this->items);
    }

    /** @return T_Value[] Values in insertion order. */
    public function values(): array {
        return Arr::values($this->items);
    }

    /** Number of entries currently stored. */
    public function count(): int {
        return count($this->items);
    }

    /** Whether the map holds no entries. */
    public function isEmpty(): bool {
        return $this->items === [];
    }

    /** @return T_Value Value at the current iteration cursor. */
    public function current(): mixed {
        return current($this->items);
    }

    /** Key at the current iteration cursor. */
    public function key(): int|string {
        return key($this->items);
    }

    /** Advance the iteration cursor. */
    public function next(): void {
        next($this->items);
    }

    /** Reset the iteration cursor to the first entry. */
    public function rewind(): void {
        reset($this->items);
    }

    /** Whether the iteration cursor still points at a valid entry. */
    public function valid(): bool {
        return key($this->items) !== null;
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
     * Always throws — the map is immutable.
     *
     * @throws BadMethodCallException
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        throw new BadMethodCallException('ImmutableMap cannot be modified.');
    }

    /**
     * Always throws — the map is immutable.
     *
     * @throws BadMethodCallException
     */
    public function offsetUnset(mixed $offset): void {
        throw new BadMethodCallException('ImmutableMap cannot be modified.');
    }

    /** @return array<T_Key, T_Value> Entries in insertion order. */
    public function toArray(): array {
        return $this->items;
    }
}
