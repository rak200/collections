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
     * @param class-string<T_Value&object>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param (Closure(T_Value, T_Value): int)|null $comparator Sort callback; null = insertion order.
     * @param iterable<T_Value> $items Initial items added in order; duplicates are ignored.
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(
        string $type = 'mixed',
        private ?Closure $comparator = null,
        iterable $items = []
    ) {
        parent::__construct($type);
        foreach ($items as $item) {
            $this->add($item);
        }
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
