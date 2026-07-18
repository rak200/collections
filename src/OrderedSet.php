<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_key_first, array_key_last, array_search, uasort;
use Closure;
use InvalidArgumentException;
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;
use Rak200\Collections\Internal\ProvidesValueFactories;

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
 * @template T_Value
 * @extends AbstractCollection<T_Value>
 * @phpstan-consistent-constructor
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class OrderedSet extends AbstractCollection {

    use ProvidesValueFactories;
use HashesValues;

    /**
     * @param string $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param (Closure(T_Value, T_Value): int)|null $comparator Sort callback; null = insertion order.
     * @param iterable<T_Value> $items Initial items added in order; duplicates are ignored.
     * @throws InvalidArgumentException When any item does not satisfy $type.
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
     * @param class-string<T> $class Class to enforce on items.
     * @param iterable<T> $items Initial items added in order; duplicates are ignored.
     * @param (Closure(T, T): int)|null $comparator Sort callback; null = insertion order.
     * @return self<T>
     * @throws InvalidArgumentException When any item does not satisfy $class.
     */
    public static function of(string $class, iterable $items = [], ?Closure $comparator = null): self {
        return new self($class, $items, $comparator);
    }

    /**
     * Untyped ordered set (no runtime type enforcement), optionally sorted.
     * Overrides {@see ProvidesValueFactories::any()} to carry a comparator.
     *
     * @param iterable<mixed> $items
     * @param (Closure(mixed, mixed): int)|null $comparator Sort callback; null = insertion order.
     * @return self<mixed>
     */
    public static function any(iterable $items = [], ?Closure $comparator = null): self {
        return new self('mixed', $items, $comparator);
    }

    /**
     * Zero-based position of the current item within the set. The internal
     * hash key is hidden so the `Iterator<int, T_Value>` contract holds.
     */
    public function key(): ?int {
        $pos = array_search(parent::key(), Arr::keys($this->items), true);
        return $pos === false ? null : $pos;
    }

    /**
     * Add an item. Returns true if newly added, false if already present.
     *
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    public function add(mixed $item): bool {
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
     * Remove an item. Returns true if it was present, false otherwise.
     *
     * @param T_Value $item
     */
    public function remove(mixed $item): bool {
        $hash = self::hashValue($item);
        if (!isset($this->items[$hash])) {
            return false;
        }
        unset($this->items[$hash]);
        return true;
    }

    /** @param T_Value $item */
    public function contains(mixed $item): bool {
        return Arr::has($this->items, self::hashValue($item));
    }

    /**
     * Return a new set containing items from both. Preserves $this's comparator.
     *
     * @param self<T_Value> $other
     * @return static
     * @throws InvalidArgumentException When $other has items incompatible with $this->type.
     */
    public function union(self $other): static {
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
     * @param self<T_Value> $other
     * @return static
     */
    public function intersection(self $other): static {
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
     * @param self<T_Value> $other
     * @return static
     */
    public function difference(self $other): static {
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
     * @param self<T_Value> $other
     */
    public function isSubsetOf(self $other): bool {
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
     * @param self<T_Value> $other
     */
    public function isSupersetOf(self $other): bool {
        return $other->isSubsetOf($this);
    }

    /**
     * First item in the current order, or null if empty.
     *
     * @return T_Value|null
     */
    public function first(): mixed {
        if ($this->items === []) {
            return null;
        }
        return $this->items[array_key_first($this->items)];
    }

    /**
     * Last item in the current order, or null if empty.
     *
     * @return T_Value|null
     */
    public function last(): mixed {
        if ($this->items === []) {
            return null;
        }
        return $this->items[array_key_last($this->items)];
    }

    /**
     * Return the items as a zero-indexed array in the current order
     * (hash keys discarded).
     *
     * @return T_Value[]
     */
    public function toArray(): array {
        return Arr::values($this->items);
    }
}
