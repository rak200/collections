<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function count, key, next, reset;
use InvalidArgumentException;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;
use Rak200\Collections\Internal\ProvidesValueFactories;

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
 * Complexity:
 * - O(1): contains / count / isEmpty / getType / iteration
 * - O(n): toArray / union / intersection / difference / isSubsetOf / isSupersetOf
 *
 * @template T_Value
 * @implements \Iterator<int, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class ImmutableSet implements \Iterator, \Countable, ToArray {

    use ProvidesValueFactories;
use HashesValues;

    /** @var array<string, T_Value> Hash → original value. */
    private array $items = [];

    private int $iterPos = 0;

    /**
     * @param string $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param iterable<T_Value> $items Initial items; duplicates are silently dropped.
     * @throws InvalidArgumentException When any item does not satisfy $type.
     */
    protected function __construct(private string $type = 'mixed', iterable $items = []) {
        foreach ($items as $item) {
            ValidatesType::checkType($this->type, $item);
            $hash = self::hashValue($item);
            if (!Arr::has($this->items, $hash)) {
                $this->items[$hash] = $item;
            }
        }
    }

    /**
     * Typed factory for class instances. Unlike the constructor, the item
     * type is inferred statically: `ImmutableSet::of(Foo::class)` is `ImmutableSet<Foo>`
     * in both PHPStan and IDE analysis.
     *
     * @template T of object
     * @param class-string<T> $class Class to enforce on items.
     * @param iterable<T> $items Initial items; duplicates are silently dropped.
     * @return self<T>
     * @throws InvalidArgumentException When any item does not satisfy $class.
     */
    public static function of(string $class, iterable $items = []): self {
        return new self($class, $items);
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

    /**
     * Configured type discriminator. Complexity: O(1).
     *
     * @return class-string<T_Value>|string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Whether the set contains $item. Complexity: O(1).
     *
     * @param T_Value $item
     */
    public function contains(mixed $item): bool {
        return Arr::has($this->items, self::hashValue($item));
    }

    /**
     * Return a new immutable set containing items from both. Resulting type matches $this.
     *
     * Complexity: O(n + m), where n = |$this| and m = |$other|.
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
     * Complexity: O(n), where n = |$this| (each membership test is O(1)).
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
     * Complexity: O(n), where n = |$this| (each membership test is O(1)).
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
     * Complexity: O(n), where n = |$this|.
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
     * Complexity: O(m), where m = |$other|.
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

    /** Number of items currently stored. Complexity: O(1). */
    public function count(): int {
        return count($this->items);
    }

    /** Whether the set holds no items. Complexity: O(1). */
    public function isEmpty(): bool {
        return $this->items === [];
    }

    /** @return T_Value|null Item at the current iteration cursor, or null past the end. Complexity: O(1). */
    public function current(): mixed {
        $key = key($this->items);
        return $key === null ? null : $this->items[$key];
    }

    /** Zero-based position within the set. Complexity: O(1). */
    public function key(): int {
        return $this->iterPos;
    }

    /** Advance the iteration cursor. Complexity: O(1). */
    public function next(): void {
        next($this->items);
        $this->iterPos++;
    }

    /** Reset the iteration cursor to the first item. Complexity: O(1). */
    public function rewind(): void {
        reset($this->items);
        $this->iterPos = 0;
    }

    /** Whether the iteration cursor still points at a valid item. Complexity: O(1). */
    public function valid(): bool {
        return key($this->items) !== null;
    }

    /**
     * Return the items as a zero-indexed array (hash keys discarded).
     *
     * Complexity: O(n).
     *
     * @return T_Value[]
     */
    public function toArray(): array {
        return Arr::values($this->items);
    }
}
