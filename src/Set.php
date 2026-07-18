<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_search;
use InvalidArgumentException;
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;

/**
 * Unique-element set. Identity is hybrid:
 * - objects are unique by {@see spl_object_id()} (same instance only)
 * - scalars (int/string/float/bool), null, and arrays are unique by value
 *
 * Common cases: membership tests, deduplication of inputs, visited-node
 * tracking in graph traversals, tag / permission collections.
 *
 * @template T_Value
 * @extends AbstractCollection<T_Value>
 * @phpstan-consistent-constructor
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Set extends AbstractCollection {

    use HashesValues;
    use ProvidesValueFactories;

    /**
     * Collection of instances of the given class, with the element type
     * inferred statically: `Set::of(Foo::class)` is `Set<Foo>`.
     *
     * @template T of object
     * @param class-string<T> $class Class to enforce on items.
     * @param iterable<T> $items Initial items added in order; duplicates are ignored.
     * @return self<T>
     * @throws InvalidArgumentException When any item is not an instance of $class.
     */
    public static function of(string $class, iterable $items = []): self {
        return new self($class, $items);
    }

    /**
     * @param string $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param iterable<T_Value> $items Initial items added in order; duplicates are ignored.
     * @throws InvalidArgumentException When any item does not satisfy $type.
     */
    protected function __construct(string $type = 'mixed', iterable $items = []) {
        parent::__construct($type);
        foreach ($items as $item) {
            $this->add($item);
        }
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
     * Return a new set containing items from both. Resulting type matches $this.
     *
     * @param self<T_Value> $other
     * @return static
     * @throws InvalidArgumentException When $other has items incompatible with $this->type.
     */
    public function union(self $other): static {
        $result = new static($this->type);
        foreach ($this->items as $item) {
            $result->add($item);
        }
        foreach ($other->items as $item) {
            $result->add($item);
        }
        return $result;
    }

    /**
     * Return a new set containing only items present in both.
     *
     * @param self<T_Value> $other
     * @return static
     */
    public function intersection(self $other): static {
        $result = new static($this->type);
        foreach ($this->items as $item) {
            if ($other->contains($item)) {
                $result->add($item);
            }
        }
        return $result;
    }

    /**
     * Return a new set containing items in $this not present in $other.
     *
     * @param self<T_Value> $other
     * @return static
     */
    public function difference(self $other): static {
        $result = new static($this->type);
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
     * Return the items as a zero-indexed array (hash keys discarded).
     *
     * @return T_Value[]
     */
    public function toArray(): array {
        return Arr::values($this->items);
    }
}
