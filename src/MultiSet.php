<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_slice, count, max, usort;
use InvalidArgumentException;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\HashesValues;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;
use Rak200\Collections\Internal\ProvidesValueFactories;

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
 * @template T_Value
 * @implements \Iterator<int, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class MultiSet implements \Iterator, \Countable, ToArray {

    use ProvidesValueFactories;
use HashesValues;

    /** @var array<string, T_Value> Hash → original item (insertion order). */
    private array $items = [];

    /** @var array<string, int> Hash → occurrence count (parallel to $items). */
    private array $counts = [];

    private int $iterPos = 0;

    /** @var list<string>|null Hash keys in iteration order, captured on rewind(). */
    private ?array $iterKeys = null;

    /**
     * @param string $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param iterable<T_Value> $items Initial items; each one increments its count by one.
     * @throws InvalidArgumentException When any item does not satisfy $type.
     */
    protected function __construct(private string $type = 'mixed', iterable $items = []) {
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
     * @param class-string<T> $class Class to enforce on items.
     * @param iterable<T> $items Initial items; each one increments its count by one.
     * @return self<T>
     * @throws InvalidArgumentException When any item does not satisfy $class.
     */
    public static function of(string $class, iterable $items = []): self {
        return new self($class, $items);
    }

    /** @return class-string<T_Value>|string */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Add $count occurrences of $item. Returns the new occurrence count.
     *
     * @param T_Value $item
     * @param int $count Number of occurrences to add (must be positive).
     * @throws InvalidArgumentException When $item violates $type, or $count is not positive.
     */
    public function add(mixed $item, int $count = 1): int {
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
     * @param T_Value $item
     * @param int $count Number of occurrences to remove (must be positive).
     * @throws InvalidArgumentException When $count is not positive.
     */
    public function remove(mixed $item, int $count = 1): int {
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
     * @param T_Value $item
     * @param int $count New count (must be non-negative).
     * @throws InvalidArgumentException When $item violates $type, or $count is negative.
     */
    public function setCount(mixed $item, int $count): void {
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
     * Occurrence count for $item, or 0 if absent.
     *
     * @param T_Value $item
     */
    public function countOf(mixed $item): int {
        return $this->counts[self::hashValue($item)] ?? 0;
    }

    /**
     * Whether $item has at least one occurrence in the bag.
     *
     * @param T_Value $item
     */
    public function contains(mixed $item): bool {
        return isset($this->counts[self::hashValue($item)]);
    }

    /** Number of distinct items in the bag. */
    public function distinct(): int {
        return count($this->items);
    }

    /**
     * Unique items in insertion order (each appearing once regardless of count).
     *
     * @return list<T_Value>
     */
    public function unique(): array {
        return Arr::values($this->items);
    }

    /**
     * Top-$n items by occurrence count (descending). Ties keep insertion order.
     *
     * @param int $n Maximum number of items to return (must be non-negative).
     * @return list<array{0: T_Value, 1: int}> Pairs of `[item, count]`.
     * @throws InvalidArgumentException When $n is negative.
     */
    public function mostCommon(int $n): array {
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

    /** Total number of occurrences across every item in the bag. */
    public function count(): int {
        $sum = 0;
        foreach ($this->counts as $c) {
            $sum += $c;
        }
        return max(0, $sum);
    }

    /** Whether the bag holds no items. */
    public function isEmpty(): bool {
        return $this->items === [];
    }

    /** Discard every item and reset iteration state. */
    public function clear(): void {
        $this->items = [];
        $this->counts = [];
        $this->iterKeys = null;
        $this->iterPos = 0;
    }

    /** @return T_Value|null Item at the current iteration position, or null past the end. */
    public function current(): mixed {
        if ($this->iterKeys === null || !isset($this->iterKeys[$this->iterPos])) {
            return null;
        }
        return $this->items[$this->iterKeys[$this->iterPos]];
    }

    /** Occurrence count at the current iteration position, exposed as the key. */
    public function key(): int {
        if ($this->iterKeys === null || !isset($this->iterKeys[$this->iterPos])) {
            return 0;
        }
        return $this->counts[$this->iterKeys[$this->iterPos]];
    }

    /** Advance the iteration position. */
    public function next(): void {
        $this->iterPos++;
    }

    /** Snapshot the current insertion order and reset the iteration position. */
    public function rewind(): void {
        $this->iterKeys = Arr::keys($this->items);
        $this->iterPos = 0;
    }

    /** Whether the iteration position still points at a snapshotted item. */
    public function valid(): bool {
        return $this->iterKeys !== null && isset($this->iterKeys[$this->iterPos]);
    }

    /**
     * Return entries as a list of `[item, count]` pairs in insertion order.
     *
     * A plain associative array would be ambiguous because object items aren't
     * representable as array keys, so pairs are used uniformly.
     *
     * @return list<array{0: T_Value, 1: int}>
     */
    public function toArray(): array {
        $out = [];
        foreach ($this->items as $hash => $item) {
            $out[] = [$item, $this->counts[$hash]];
        }
        return $out;
    }
}
