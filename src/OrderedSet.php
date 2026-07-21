<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Closure;
use InvalidArgumentException;
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;

use function array_key_first;
use function array_key_last;
use function array_search;
use function uasort;

/**
 * Unique-element set with a predictable iteration order.
 *
 * Identity is hybrid (objects by `spl_object_id`, scalars/null/arrays by
 * value — same as {@see Set}). The order is configurable:
 * - default (no comparator): insertion order
 * - with a comparator (`fn($a, $b): int`, usort-style): re-sorted on every
 *   `add()` so iteration always reflects the comparator's order
 *
 * Common cases: leaderboards and rankings (with a comparator), insertion-
 * ordered membership tracking when you also want stable `first()` / `last()`,
 * UI-facing sorted-distinct lists.
 *
 * Complexity:
 * - O(1): contains / remove / first / last / count / isEmpty / clear / getType
 * - O(n): toArray / union / intersection / difference / isSubsetOf / isSupersetOf
 * - add: O(1) in insertion order, O(n log n) with a comparator (re-sorted on every add)
 *
 * (Note: like {@see Set}, key() resolves the iteration position in O(n).)
 *
 * @template T_Value
 *
 * @extends AbstractCollection<T_Value>
 *
 * @phpstan-consistent-constructor
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class OrderedSet extends AbstractCollection
{
    use ProvidesValueFactories;
    use HashesValues;

    /**
     * @param string                                $type       class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip
     * @param null|(Closure(T_Value, T_Value): int) $comparator sort callback; null = insertion order
     * @param iterable<T_Value>                     $items      initial items added in order; duplicates are ignored
     *
     * @throws InvalidArgumentException when any item does not satisfy $type
     */
    protected function __construct(
        string $type = 'mixed',
        iterable $items = [],
        private ?Closure $comparator = null
    ) {
        parent::__construct($type);
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Typed factory for class instances. Unlike the constructor, the item
     * type is inferred statically: `OrderedSet::of(Foo::class)` is
     * `OrderedSet<Foo>` in both PHPStan and IDE analysis.
     *
     * @template T of object
     *
     * @param class-string<T>           $class      class to enforce on items
     * @param iterable<T>               $items      initial items added in order; duplicates are ignored
     * @param null|(Closure(T, T): int) $comparator sort callback; null = insertion order
     *
     * @return self<T>
     *
     * @throws InvalidArgumentException when any item does not satisfy $class
     */
    public static function of(string $class, iterable $items = [], ?Closure $comparator = null): self
    {
        return new self($class, $items, $comparator);
    }

    /**
     * Untyped ordered set (no runtime type enforcement), optionally sorted.
     * Overrides {@see ProvidesValueFactories::any()} to carry a comparator.
     *
     * @param iterable<mixed>                   $items
     * @param null|(Closure(mixed, mixed): int) $comparator sort callback; null = insertion order
     *
     * @return self<mixed>
     */
    public static function any(iterable $items = [], ?Closure $comparator = null): self
    {
        return new self('mixed', $items, $comparator);
    }

    /**
     * Zero-based position of the current item within the set. The internal
     * hash key is hidden so the `Iterator<int, T_Value>` contract holds.
     *
     * Complexity: O(n) — a linear scan maps the hash key to its position.
     */
    public function key(): ?int
    {
        $pos = array_search(parent::key(), Arr::keys($this->items), true);

        return $pos === false ? null : $pos;
    }

    /**
     * Add an item. Returns true if newly added, false if already present.
     *
     * Complexity: O(1) with insertion order; O(n log n) when a comparator is
     * set, since the backing array is re-sorted on every add.
     *
     * @param T_Value $item
     *
     * @throws InvalidArgumentException
     */
    public function add(mixed $item): bool
    {
        ValidatesType::checkType($this->type, $item);
        $hash = self::hashValue($item);
        if (Arr::has($this->items, $hash)) {
            return false;
        }
        $this->items[$hash] = $item;
        if ($this->comparator !== null) {
            uasort($this->items, $this->comparator);
        }

        return true;
    }

    /**
     * Remove an item. Returns true if it was present, false otherwise. Complexity: O(1).
     *
     * @param T_Value $item
     */
    public function remove(mixed $item): bool
    {
        $hash = self::hashValue($item);
        if (!isset($this->items[$hash])) {
            return false;
        }
        unset($this->items[$hash]);

        return true;
    }

    /**
     * Whether the set contains $item. Complexity: O(1).
     *
     * @param T_Value $item
     */
    public function contains(mixed $item): bool
    {
        return Arr::has($this->items, self::hashValue($item));
    }

    /**
     * Return a new set containing items from both. Preserves $this's comparator.
     *
     * Complexity: O(n + m) with insertion order (n = |$this|, m = |$other|);
     * higher when a comparator re-sorts on each add.
     *
     * @param self<T_Value> $other
     *
     * @throws InvalidArgumentException when $other has items incompatible with $this->type
     */
    public function union(self $other): static
    {
        $result = new static($this->type, comparator: $this->comparator);
        foreach ($this->items as $item) {
            $result->add($item);
        }
        foreach ($other->items as $item) {
            $result->add($item);
        }

        return $result;
    }

    /**
     * Return a new set containing only items present in both. Preserves $this's comparator.
     *
     * Complexity: O(n) with insertion order (n = |$this|); higher when a
     * comparator re-sorts on each add.
     *
     * @param self<T_Value> $other
     */
    public function intersection(self $other): static
    {
        $result = new static($this->type, comparator: $this->comparator);
        foreach ($this->items as $item) {
            if ($other->contains($item)) {
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * Return a new set containing items in $this not present in $other. Preserves $this's comparator.
     *
     * Complexity: O(n) with insertion order (n = |$this|); higher when a
     * comparator re-sorts on each add.
     *
     * @param self<T_Value> $other
     */
    public function difference(self $other): static
    {
        $result = new static($this->type, comparator: $this->comparator);
        foreach ($this->items as $item) {
            if (!$other->contains($item)) {
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * Whether every item in $this is also in $other.
     *
     * Complexity: O(n), where n = |$this|.
     *
     * @param self<T_Value> $other
     */
    public function isSubsetOf(self $other): bool
    {
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
     * @param self<T_Value> $other
     */
    public function isSupersetOf(self $other): bool
    {
        return $other->isSubsetOf($this);
    }

    /**
     * First item in the current order, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function first(): mixed
    {
        if ($this->items === []) {
            return null;
        }

        return $this->items[array_key_first($this->items)];
    }

    /**
     * Last item in the current order, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function last(): mixed
    {
        if ($this->items === []) {
            return null;
        }

        return $this->items[array_key_last($this->items)];
    }

    /**
     * Return the items as a zero-indexed array in the current order
     * (hash keys discarded).
     *
     * Complexity: O(n).
     *
     * @return T_Value[]
     */
    public function toArray(): array
    {
        return Arr::values($this->items);
    }
}
