<?php

declare(strict_types=1);

namespace Rak200\Collections;

use InvalidArgumentException;
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;

use function array_search;

/**
 * Unique-element set. Identity is hybrid:
 * - objects are unique by {@see spl_object_id()} (same instance only)
 * - scalars (int/string/float/bool), null, and arrays are unique by value
 *
 * Common cases: membership tests, deduplication of inputs, visited-node
 * tracking in graph traversals, tag / permission collections.
 *
 * Complexity:
 * - O(1): add / remove / contains / count / isEmpty / clear / getType
 * - O(n): toArray / union / intersection / difference / isSubsetOf / isSupersetOf
 *
 * (Note: during iteration key() resolves the position in O(n), so a keyed
 * foreach is O(n²); iterate values, or use toArray(), when the position
 * isn't needed.)
 *
 * @template T_Value
 *
 * @extends AbstractCollection<T_Value>
 *
 * @phpstan-consistent-constructor
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Set extends AbstractCollection
{
    use HashesValues;
    use ProvidesValueFactories;

    /**
     * @param string            $type  class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip
     * @param iterable<T_Value> $items initial items added in order; duplicates are ignored
     *
     * @throws InvalidArgumentException when any item does not satisfy $type
     */
    protected function __construct(string $type = 'mixed', iterable $items = [])
    {
        parent::__construct($type);
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Collection of instances of the given class, with the element type
     * inferred statically: `Set::of(Foo::class)` is `Set<Foo>`.
     *
     * @template T of object
     *
     * @param class-string<T> $class class to enforce on items
     * @param iterable<T>     $items initial items added in order; duplicates are ignored
     *
     * @return self<T>
     *
     * @throws InvalidArgumentException when any item is not an instance of $class
     */
    public static function of(string $class, iterable $items = []): self
    {
        return new self($class, $items);
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
     * Add an item. Returns true if newly added, false if already present. Complexity: O(1).
     *
     * @param T_Value $item
     *
     * @throws InvalidArgumentException
     */
    public function add(mixed $item): bool
    {
        ValidatesType::checkType($this->type, $item);
        $hash = self::hashValue($item);
        if (Arr::hasKey($this->items, $hash)) {
            return false;
        }
        $this->items[$hash] = $item;

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
        return Arr::hasKey($this->items, self::hashValue($item));
    }

    /**
     * Return a new set containing items from both. Resulting type matches $this.
     *
     * Complexity: O(n + m), where n = |$this| and m = |$other|.
     *
     * @param self<T_Value> $other
     *
     * @throws InvalidArgumentException when $other has items incompatible with $this->type
     */
    public function union(self $other): static
    {
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
     * Complexity: O(n), where n = |$this| (each membership test is O(1)).
     *
     * @param self<T_Value> $other
     */
    public function intersection(self $other): static
    {
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
     * Complexity: O(n), where n = |$this| (each membership test is O(1)).
     *
     * @param self<T_Value> $other
     */
    public function difference(self $other): static
    {
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
     * Return the items as a zero-indexed array (hash keys discarded).
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
