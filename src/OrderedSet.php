<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_key_exists;
use function array_key_first;
use function array_key_last;
use function array_values;
use function uasort;
use Closure;
use InvalidArgumentException;
use Rak200\Collections\Internal\HashesValues;

/**
 * Unique-element set with a predictable iteration order.
 *
 * Identity is hybrid (objects by `spl_object_id`, scalars/null/arrays by
 * value — same as {@see Set}). The order is configurable:
 * - default (no comparator): insertion order
 * - with a comparator (`fn($a, $b): int`, usort-style): re-sorted on every
 *   `add()` so iteration always reflects the comparator's order
 *
 * @template T_Value
 * @extends AbstractCollection<T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class OrderedSet extends AbstractCollection {

    use HashesValues;

    /**
     * @param class-string<T_Value>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param (Closure(T_Value, T_Value): int)|null $comparator Sort callback; null = insertion order.
     * @param iterable<T_Value> $items Initial items added in order; duplicates are ignored.
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(
        string $type = 'mixed',
        iterable $items = [],
        private ?Closure $comparator = null
    ) {
        parent::__construct($type);
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function key(): int {
        return array_flip(array_keys($this->items))[parent::key()] ?? null;
    }

    /**
     * Add an item. Returns true if newly added, false if already present.
     *
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    public function add(mixed $item): bool {
        $this->checkType($item);
        $hash = self::hashValue($item);
        if (array_key_exists($hash, $this->items)) {
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
        return array_key_exists(self::hashValue($item), $this->items);
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
        return array_values($this->items);
    }
}
