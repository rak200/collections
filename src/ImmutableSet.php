<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_key_exists;
use function array_values;
use function count;
use function current;
use function key;
use function next;
use function reset;
use InvalidArgumentException;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ValidatesType;

/**
 * Read-only counterpart to {@see Set}. Items are fixed at construction;
 * the public API exposes only `contains()`, the set-algebra operators,
 * and iteration / `toArray` / `count`.
 *
 * Set-algebra methods (`union`, `intersection`, `difference`) accept either
 * a {@see Set} or another {@see ImmutableSet}, and always return a new
 * `ImmutableSet`. Subset/superset comparisons accept either side too.
 *
 * Identity is the same hybrid scheme used by {@see Set}: objects by
 * `spl_object_id`, scalars/null/arrays by value.
 *
 * Common cases: allow-lists / deny-lists, value-object membership tables,
 * read-only snapshots returned from APIs, frozen configuration sets.
 *
 * @template T_Value
 * @implements \Iterator<int, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class ImmutableSet implements \Iterator, \Countable, ToArray {

    use HashesValues;

    /** @var array<string, T_Value> Hash → original value. */
    private array $items = [];

    private int $iterPos = 0;

    /**
     * @param class-string<T_Value>|'mixed'|'object'|'int'|'string'|'bool'|'float'|'array'|'iterable'|'callable' $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param iterable<T_Value> $items Initial items; duplicates are silently dropped.
     * @throws InvalidArgumentException When any item does not satisfy $type.
     */
    public function __construct(private string $type = 'mixed', iterable $items = []) {
        foreach ($items as $item) {
            ValidatesType::checkType($this->type, $item);
            $hash = self::hashValue($item);
            if (!array_key_exists($hash, $this->items)) {
                $this->items[$hash] = $item;
            }
        }
    }

    /**
     * Build an immutable copy of the given {@see Set}, preserving its type.
     *
     * @template T
     * @param Set<T> $set
     * @return self<T>
     */
    public static function fromSet(Set $set): self {
        return new self($set->getType(), $set);
    }

    /** @return class-string<T_Value>|string */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Whether the set contains $item.
     *
     * @param T_Value $item
     */
    public function contains(mixed $item): bool {
        return array_key_exists(self::hashValue($item), $this->items);
    }

    /**
     * Return a new immutable set containing items from both. Resulting type matches $this.
     *
     * @param self<T_Value>|Set<T_Value> $other
     * @return self<T_Value>
     * @throws InvalidArgumentException When $other has items incompatible with $this->type.
     */
    public function union(self|Set $other): self {
        return new self($this->type, [...$this->toArray(), ...$other->toArray()]);
    }

    /**
     * Return a new immutable set containing only items present in both.
     *
     * @param self<T_Value>|Set<T_Value> $other
     * @return self<T_Value>
     */
    public function intersection(self|Set $other): self {
        $items = [];
        foreach ($this->items as $item) {
            if ($other->contains($item)) {
                $items[] = $item;
            }
        }
        return new self($this->type, $items);
    }

    /**
     * Return a new immutable set containing items in $this not present in $other.
     *
     * @param self<T_Value>|Set<T_Value> $other
     * @return self<T_Value>
     */
    public function difference(self|Set $other): self {
        $items = [];
        foreach ($this->items as $item) {
            if (!$other->contains($item)) {
                $items[] = $item;
            }
        }
        return new self($this->type, $items);
    }

    /**
     * Whether every item in $this is also in $other.
     *
     * @param self<T_Value>|Set<T_Value> $other
     */
    public function isSubsetOf(self|Set $other): bool {
        foreach ($this->items as $item) {
            if (!$other->contains($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Whether every item in $other is also in $this.
     *
     * @param self<T_Value>|Set<T_Value> $other
     */
    public function isSupersetOf(self|Set $other): bool {
        foreach ($other as $item) {
            if (!$this->contains($item)) {
                return false;
            }
        }
        return true;
    }

    /** Number of items currently stored. */
    public function count(): int {
        return count($this->items);
    }

    /** Whether the set holds no items. */
    public function isEmpty(): bool {
        return $this->items === [];
    }

    /** @return T_Value Item at the current iteration cursor. */
    public function current(): mixed {
        return current($this->items);
    }

    /** Zero-based position within the set. */
    public function key(): int {
        return $this->iterPos;
    }

    /** Advance the iteration cursor. */
    public function next(): void {
        next($this->items);
        $this->iterPos++;
    }

    /** Reset the iteration cursor to the first item. */
    public function rewind(): void {
        reset($this->items);
        $this->iterPos = 0;
    }

    /** Whether the iteration cursor still points at a valid item. */
    public function valid(): bool {
        return key($this->items) !== null;
    }

    /**
     * Return the items as a zero-indexed array (hash keys discarded).
     *
     * @return T_Value[]
     */
    public function toArray(): array {
        return array_values($this->items);
    }
}
