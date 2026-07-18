<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_pop, count, usort;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\ValidatesType;
use Rak200\Utils\Arr;
use InvalidArgumentException;
use Rak200\Collections\Internal\ProvidesValueFactories;

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
 * @template T_Value
 * @implements \Iterator<int, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class PriorityQueue implements \Iterator, \Countable, ToArray {

    use ProvidesValueFactories;
/** @var list<array{priority: int|float, sequence: int, item: T_Value}> */
    private array $heap = [];

    private int $sequence = 0;
    private int $iterPos = 0;

    /** @var list<T_Value>|null */
    private ?array $iterSnapshot = null;

    /**
     * @param string $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param iterable<T_Value> $items Initial items enqueued at priority 0 in iteration order.
     * @throws InvalidArgumentException When any item does not satisfy $type.
     */
    protected function __construct(private string $type = 'mixed', iterable $items = []) {
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
     * @param class-string<T> $class Class to enforce on items.
     * @param iterable<T> $items Initial items enqueued at priority 0 in iteration order.
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
     * Insert an item with the given priority. Higher priority is served first.
     *
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    public function enqueue(mixed $item, int|float $priority): void {
        ValidatesType::checkType($this->type, $item);
        $this->heap[] = [
            'priority' => $priority,
            'sequence' => $this->sequence++,
            'item' => $item,
        ];
        $this->siftUp(count($this->heap) - 1);
    }

    /**
     * Remove and return the highest-priority item, or null if empty.
     *
     * @return T_Value|null
     */
    public function dequeue(): mixed {
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
     * Return the highest-priority item without removing it, or null if empty.
     *
     * @return T_Value|null
     */
    public function peek(): mixed {
        return $this->heap[0]['item'] ?? null;
    }

    /** Number of items currently in the queue. */
    public function count(): int {
        return count($this->heap);
    }

    /** Whether the queue holds no items. */
    public function isEmpty(): bool {
        return $this->heap === [];
    }

    /** Discard all items and reset iteration state. */
    public function clear(): void {
        $this->heap = [];
        $this->sequence = 0;
        $this->iterSnapshot = null;
        $this->iterPos = 0;
    }

    /** Negative when $i should be served before $j (closer to top of heap). */
    private function compare(int $i, int $j): int {
        $a = $this->heap[$i];
        $b = $this->heap[$j];
        if ($a['priority'] !== $b['priority']) {
            return $a['priority'] < $b['priority'] ? 1 : -1;
        }
        return $a['sequence'] <=> $b['sequence'];
    }

    private function siftUp(int $i): void {
        while ($i > 0) {
            $parent = ($i - 1) >> 1;
            if ($this->compare($i, $parent) >= 0) {
                return;
            }
            [$this->heap[$i], $this->heap[$parent]] = [$this->heap[$parent], $this->heap[$i]];
            $i = $parent;
        }
    }

    private function siftDown(int $i): void {
        $n = count($this->heap);
        while (true) {
            $left = 2 * $i + 1;
            $right = 2 * $i + 2;
            $best = $i;
            if ($left < $n && $this->compare($left, $best) < 0) {
                $best = $left;
            }
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
    private function sortedItems(): array {
        $copy = $this->heap;
        usort($copy, static function (array $a, array $b): int {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] < $b['priority'] ? 1 : -1;
            }
            return $a['sequence'] <=> $b['sequence'];
        });
        $items = [];
        foreach ($copy as $entry) {
            $items[] = $entry['item'];
        }
        return $items;
    }

    /** @return T_Value|null Item at the current iteration position, or null past the end. */
    public function current(): mixed {
        return $this->iterSnapshot[$this->iterPos] ?? null;
    }

    /** Zero-based position within the sorted snapshot. */
    public function key(): int {
        return $this->iterPos;
    }

    /** Advance the iteration position. */
    public function next(): void {
        $this->iterPos++;
    }

    /** Build a sorted snapshot and reset the iteration position. */
    public function rewind(): void {
        $this->iterSnapshot = $this->sortedItems();
        $this->iterPos = 0;
    }

    /** Whether the iteration position still points at a snapshotted item. */
    public function valid(): bool {
        return $this->iterSnapshot !== null && isset($this->iterSnapshot[$this->iterPos]);
    }

    /**
     * Return items in extraction order (highest priority first), without
     * mutating the queue.
     *
     * @return list<T_Value>
     */
    public function toArray(): array {
        return $this->sortedItems();
    }
}
