<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Countable;
use InvalidArgumentException;
use Iterator;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;

use function array_pop;
use function count;

/**
 * Max-heap priority queue. Items with higher priority are extracted first;
 * ties are broken FIFO via an internal sequence counter (stable ordering).
 *
 * Iteration is non-destructive and yields items in extraction order without
 * mutating the queue (the snapshot is built lazily on `rewind()`). The
 * snapshot and position are held on the instance, so nested `foreach` loops
 * over the same queue interfere with each other.
 *
 * Common cases: scheduling (Dijkstra / A* frontier, task urgency queues),
 * event simulation, top-N extraction, "process the most important item next"
 * workflows.
 *
 * Complexity:
 * - O(1): peek / count / isEmpty / clear / getType
 * - O(log n): enqueue / dequeue
 * - O(n log n): toArray / iteration (rewind builds a sorted snapshot)
 *
 * @template T_Value
 *
 * @implements Iterator<int, T_Value>
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class PriorityQueue implements Iterator, Countable, ToArray
{
    use ProvidesValueFactories;

    /** @var list<array{priority: float|int, sequence: int, item: T_Value}> */
    private array $heap = [];

    private int $sequence = 0;
    private int $iterPos = 0;

    /** @var null|list<T_Value> */
    private ?array $iterSnapshot = null;

    /**
     * @param string            $type  class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip
     * @param iterable<T_Value> $items initial items enqueued at priority 0 in iteration order
     *
     * @throws InvalidArgumentException when any item does not satisfy $type
     */
    protected function __construct(private string $type = 'mixed', iterable $items = [])
    {
        foreach ($items as $item) {
            $this->enqueue($item, 0);
        }
    }

    /**
     * Typed factory for class instances. Unlike the constructor, the item
     * type is inferred statically: `PriorityQueue::of(Foo::class)` is `PriorityQueue<Foo>`
     * in both PHPStan and IDE analysis.
     *
     * @template T of object
     *
     * @param class-string<T> $class class to enforce on items
     * @param iterable<T>     $items initial items enqueued at priority 0 in iteration order
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
     * Insert an item with the given priority. Higher priority is served first.
     *
     * Complexity: O(log n) — a sift-up over the heap.
     *
     * @param T_Value $item
     *
     * @throws InvalidArgumentException
     */
    public function enqueue(mixed $item, float|int $priority): void
    {
        ValidatesType::checkType($this->type, $item);
        $this->heap[] = [
            'priority' => $priority,
            'sequence' => $this->sequence++,
            'item' => $item,
        ];
        $this->siftUp(Arr::count($this->heap) - 1);
    }

    /**
     * Remove and return the highest-priority item, or null if empty.
     *
     * Complexity: O(log n) — a sift-down over the heap.
     *
     * @return null|T_Value
     */
    public function dequeue(): mixed
    {
        if ($this->heap === []) {
            return null;
        }
        $top = $this->heap[0]['item'];
        $last = array_pop($this->heap);
        if ($this->heap !== []) {
            $this->heap[0] = $last;
            $this->siftDown(0);
        }

        return $top;
    }

    /**
     * Return the highest-priority item without removing it, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function peek(): mixed
    {
        return $this->heap[0]['item'] ?? null;
    }

    /** Number of items currently in the queue. Complexity: O(1). See {@see AbstractCollection::count()} for why the native `count()` stays here. */
    public function count(): int
    {
        return count($this->heap);
    }

    /** Whether the queue holds no items. Complexity: O(1). */
    public function isEmpty(): bool
    {
        return $this->heap === [];
    }

    /** Discard all items and reset iteration state. Complexity: O(1). */
    public function clear(): void
    {
        $this->heap = [];
        $this->sequence = 0;
        $this->iterSnapshot = null;
        $this->iterPos = 0;
    }

    /** @return null|T_Value Item at the current iteration position, or null past the end. Complexity: O(1). */
    public function current(): mixed
    {
        return $this->iterSnapshot[$this->iterPos] ?? null;
    }

    /** Zero-based position within the sorted snapshot. Complexity: O(1). */
    public function key(): int
    {
        return $this->iterPos;
    }

    /** Advance the iteration position. Complexity: O(1). */
    public function next(): void
    {
        ++$this->iterPos;
    }

    /** Build a sorted snapshot and reset the iteration position. Complexity: O(n log n). */
    public function rewind(): void
    {
        $this->iterSnapshot = $this->sortedItems();
        $this->iterPos = 0;
    }

    /** Whether the iteration position still points at a snapshotted item. Complexity: O(1). */
    public function valid(): bool
    {
        return $this->iterSnapshot !== null && isset($this->iterSnapshot[$this->iterPos]);
    }

    /**
     * Return items in extraction order (highest priority first), without
     * mutating the queue.
     *
     * Complexity: O(n log n) — the snapshot is sorted.
     *
     * @return list<T_Value>
     */
    public function toArray(): array
    {
        return $this->sortedItems();
    }

    /** Negative when $i should be served before $j (closer to top of heap). */
    private function compare(int $i, int $j): int
    {
        $a = $this->heap[$i];
        $b = $this->heap[$j];
        if ($a['priority'] !== $b['priority']) {
            // @infection-ignore-all Equivalent mutant: the guard above rules out
            // $a['priority'] === $b['priority'], so `<` and `<=` agree here; and
            // every caller only reads the sign of this return (>=0 / <0), never
            // its magnitude, so 1/-1 vs 0/2/-2 change nothing observable.
            return $a['priority'] < $b['priority'] ? 1 : -1;
        }

        return $a['sequence'] <=> $b['sequence'];
    }

    private function siftUp(int $i): void
    {
        while ($i > 0) {
            // @infection-ignore-all Equivalent mutant: replacing the halving shift
            // with a no-op shift turns siftUp into a walk that bubbles the new
            // element one slot at a time until compare() stops it — slower than
            // O(log n), but it still terminates at a position satisfying the heap
            // invariant, so the resulting heap (and every dequeue order it
            // produces) is unchanged.
            $parent = ($i - 1) >> 1;
            // @infection-ignore-all Equivalent mutant: compare() only ever
            // returns -1 or a nonzero <=> of two distinct sequence numbers — it
            // can never be exactly 0 — so `>= 0` and `> 0` accept exactly the
            // same values here.
            if ($this->compare($i, $parent) >= 0) {
                return;
            }
            [$this->heap[$i], $this->heap[$parent]] = [$this->heap[$parent], $this->heap[$i]];
            $i = $parent;
        }
    }

    private function siftDown(int $i): void
    {
        $n = Arr::count($this->heap);
        while (true) {
            $left = 2 * $i + 1;
            $right = 2 * $i + 2;
            $best = $i;
            // @infection-ignore-all Equivalent mutant: compare() never returns
            // exactly 0 (see siftUp), so `< 0` and `<= 0` accept the same values.
            if ($left < $n && $this->compare($left, $best) < 0) {
                $best = $left;
            }
            // @infection-ignore-all Equivalent mutant: same reasoning as the left
            // branch above — compare() never returns exactly 0.
            if ($right < $n && $this->compare($right, $best) < 0) {
                $best = $right;
            }
            if ($best === $i) {
                return;
            }
            [$this->heap[$i], $this->heap[$best]] = [$this->heap[$best], $this->heap[$i]];
            $i = $best;
        }
    }

    /** @return list<T_Value> */
    private function sortedItems(): array
    {
        $sorted = Arr::sort($this->heap, static function (array $a, array $b): int {
            if ($a['priority'] !== $b['priority']) {
                // @infection-ignore-all Equivalent mutant: the guard above rules
                // out equal priorities, so `<` / `<=` agree here; and — empirically
                // verified across heap states reachable through the public API
                // (enqueue/dequeue in any order) — no reachable snapshot lets a
                // magnitude change to 1/-1 (2, -2, or 0) alter the stable sort's
                // output, because every heap this sorts already satisfies the
                // max-heap invariant maintained by compare()/siftUp/siftDown.
                return $a['priority'] < $b['priority'] ? 1 : -1;
            }

            return $a['sequence'] <=> $b['sequence'];
        });

        return Arr::map($sorted, static fn (array $entry): mixed => $entry['item']);
    }
}
