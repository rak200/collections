<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Countable;
use InvalidArgumentException;
use Iterator;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;

use function array_slice;
use function count;
use function max;
use function usort;

/**
 * Bag / occurrence counter. Records how many times each unique value has been
 * added — the same hybrid identity used by {@see Set} (objects by
 * `spl_object_id`, scalars/null/arrays by value).
 *
 * `count()` returns the total occurrence count across all items (the size of
 * the bag); `distinct()` returns the number of unique items. Iteration yields
 * `value => count` pairs in insertion order.
 *
 * Common cases: frequency tables, word counts, histograms, vote tallies,
 * inventory / stock counts, any "how many of each?" tally.
 *
 * Complexity (d = number of distinct items):
 * - O(1): add / remove / setCount / countOf / contains / distinct / isEmpty / clear / getType
 * - O(d): unique / count / toArray / iteration (rewind)
 * - O(d log d): mostCommon
 *
 * @template T_Value
 *
 * @implements Iterator<int, T_Value>
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class MultiSet implements Iterator, Countable, ToArray
{
    use ProvidesValueFactories;
    use HashesValues;

    /** @var array<string, T_Value> Hash → original item (insertion order). */
    private array $items = [];

    /** @var array<string, int> Hash → occurrence count (parallel to). */
    private array $counts = [];

    private int $iterPos = 0;

    /** @var null|list<string> Hash keys in iteration order, captured on rewind(). */
    private ?array $iterKeys = null;

    /**
     * @param string            $type  class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip
     * @param iterable<T_Value> $items initial items; each one increments its count by one
     *
     * @throws InvalidArgumentException when any item does not satisfy $type
     */
    protected function __construct(private string $type = 'mixed', iterable $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Typed factory for class instances. Unlike the constructor, the item
     * type is inferred statically: `MultiSet::of(Foo::class)` is `MultiSet<Foo>`
     * in both PHPStan and IDE analysis.
     *
     * @template T of object
     *
     * @param class-string<T> $class class to enforce on items
     * @param iterable<T>     $items initial items; each one increments its count by one
     *
     * @return self<T>
     *
     * @throws InvalidArgumentException when any item does not satisfy $class
     */
    public static function of(string $class, iterable $items = []): self
    {
        return new self($class, $items);
    }

    /**
     * Configured item type. Complexity: O(1).
     *
     * @return class-string<T_Value>|string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Add $count occurrences of $item. Returns the new occurrence count. Complexity: O(1).
     *
     * @param T_Value $item
     * @param int     $count number of occurrences to add (must be positive)
     *
     * @throws InvalidArgumentException when $item violates $type, or $count is not positive
     */
    public function add(mixed $item, int $count = 1): int
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Count must be a positive integer.');
        }
        ValidatesType::checkType($this->type, $item);
        $hash = self::hashValue($item);
        if (!isset($this->items[$hash])) {
            $this->items[$hash] = $item;
            $this->counts[$hash] = 0;
        }
        $this->counts[$hash] += $count;

        return $this->counts[$hash];
    }

    /**
     * Remove $count occurrences of $item. Drops the item entirely when its
     * count reaches zero. Returns the remaining occurrence count (0 if absent
     * or fully removed).
     *
     * Complexity: O(1).
     *
     * @param T_Value $item
     * @param int     $count number of occurrences to remove (must be positive)
     *
     * @throws InvalidArgumentException when $count is not positive
     */
    public function remove(mixed $item, int $count = 1): int
    {
        if ($count < 1) {
            throw new InvalidArgumentException('Count must be a positive integer.');
        }
        $hash = self::hashValue($item);
        if (!isset($this->counts[$hash])) {
            return 0;
        }
        $this->counts[$hash] -= $count;
        if ($this->counts[$hash] <= 0) {
            unset($this->items[$hash], $this->counts[$hash]);

            return 0;
        }

        return $this->counts[$hash];
    }

    /**
     * Set the occurrence count for $item directly. Removes the item when
     * $count is zero.
     *
     * Complexity: O(1).
     *
     * @param T_Value $item
     * @param int     $count new count (must be non-negative)
     *
     * @throws InvalidArgumentException when $item violates $type, or $count is negative
     */
    public function setCount(mixed $item, int $count): void
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Count must be non-negative.');
        }
        ValidatesType::checkType($this->type, $item);
        $hash = self::hashValue($item);
        if ($count === 0) {
            unset($this->items[$hash], $this->counts[$hash]);

            return;
        }
        if (!isset($this->items[$hash])) {
            $this->items[$hash] = $item;
        }
        $this->counts[$hash] = $count;
    }

    /**
     * Occurrence count for $item, or 0 if absent. Complexity: O(1).
     *
     * @param T_Value $item
     */
    public function countOf(mixed $item): int
    {
        return $this->counts[self::hashValue($item)] ?? 0;
    }

    /**
     * Whether $item has at least one occurrence in the bag. Complexity: O(1).
     *
     * @param T_Value $item
     */
    public function contains(mixed $item): bool
    {
        return isset($this->counts[self::hashValue($item)]);
    }

    /** Number of distinct items in the bag. Complexity: O(1). */
    public function distinct(): int
    {
        return count($this->items);
    }

    /**
     * Unique items in insertion order (each appearing once regardless of count).
     *
     * Complexity: O(d) in the number of distinct items.
     *
     * @return list<T_Value>
     */
    public function unique(): array
    {
        return Arr::values($this->items);
    }

    /**
     * Top-$n items by occurrence count (descending). Ties keep insertion order.
     *
     * Complexity: O(d log d) in the number of distinct items (a full sort).
     *
     * @param int $n maximum number of items to return (must be non-negative)
     *
     * @return list<array{0: T_Value, 1: int}> pairs of `[item, count]`
     *
     * @throws InvalidArgumentException when $n is negative
     */
    public function mostCommon(int $n): array
    {
        if ($n < 0) {
            throw new InvalidArgumentException('Limit must be non-negative.');
        }
        $hashes = Arr::keys($this->items);
        $order = [];
        foreach ($hashes as $i => $hash) {
            $order[$hash] = $i;
        }
        usort($hashes, function (string $a, string $b) use ($order): int {
            $cmp = $this->counts[$b] <=> $this->counts[$a];
            if ($cmp !== 0) {
                return $cmp;
            }

            return $order[$a] <=> $order[$b];
        });
        $top = array_slice($hashes, 0, $n);
        $result = [];
        foreach ($top as $hash) {
            $result[] = [$this->items[$hash], $this->counts[$hash]];
        }

        return $result;
    }

    /** Total number of occurrences across every item in the bag. Complexity: O(d) in the number of distinct items. */
    public function count(): int
    {
        $sum = 0;
        foreach ($this->counts as $c) {
            $sum += $c;
        }

        // @infection-ignore-all Equivalent mutant: every stored count is kept
        // strictly positive by add()/remove()/setCount() (a count reaching zero
        // deletes the entry), so the sum of stored counts can never be negative —
        // this clamp has no reachable input to change.
        return max(0, $sum);
    }

    /** Whether the bag holds no items. Complexity: O(1). */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** Discard every item and reset iteration state. Complexity: O(1). */
    public function clear(): void
    {
        $this->items = [];
        $this->counts = [];
        $this->iterKeys = null;
        // @infection-ignore-all Equivalent mutant: current()/key()/valid() all
        // check $iterKeys === null before ever reading $iterPos, and clear()
        // always resets $iterKeys to null first — so the value restarted here is
        // unobservable until the next rewind() overwrites it anyway.
        $this->iterPos = 0;
    }

    /** @return null|T_Value Item at the current iteration position, or null past the end. Complexity: O(1). */
    public function current(): mixed
    {
        if ($this->iterKeys === null || !isset($this->iterKeys[$this->iterPos])) {
            return null;
        }

        return $this->items[$this->iterKeys[$this->iterPos]];
    }

    /** Occurrence count at the current iteration position, exposed as the key. Complexity: O(1). */
    public function key(): int
    {
        if ($this->iterKeys === null || !isset($this->iterKeys[$this->iterPos])) {
            return 0;
        }

        return $this->counts[$this->iterKeys[$this->iterPos]];
    }

    /** Advance the iteration position. Complexity: O(1). */
    public function next(): void
    {
        ++$this->iterPos;
    }

    /** Snapshot the current insertion order and reset the iteration position. Complexity: O(d) in the number of distinct items. */
    public function rewind(): void
    {
        $this->iterKeys = Arr::keys($this->items);
        $this->iterPos = 0;
    }

    /** Whether the iteration position still points at a snapshotted item. Complexity: O(1). */
    public function valid(): bool
    {
        return $this->iterKeys !== null && isset($this->iterKeys[$this->iterPos]);
    }

    /**
     * Return entries as a list of `[item, count]` pairs in insertion order.
     *
     * A plain associative array would be ambiguous because object items aren't
     * representable as array keys, so pairs are used uniformly.
     *
     * Complexity: O(d) in the number of distinct items.
     *
     * @return list<array{0: T_Value, 1: int}>
     */
    public function toArray(): array
    {
        $out = [];
        foreach ($this->items as $hash => $item) {
            $out[] = [$item, $this->counts[$hash]];
        }

        return $out;
    }
}
