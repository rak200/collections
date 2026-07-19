<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\ValidatesType;
use InvalidArgumentException;

/**
 * Fixed-capacity FIFO buffer with overwrite semantics.
 *
 * Behaves like {@see Queue} until full, at which point the next `push()`
 * evicts the oldest item to make room. Useful for sliding windows, log
 * ringbuffers, and any "keep the last N" workflow.
 *
 * Complexity:
 * - O(1): push / pop / peek / capacity / isFull / count / isEmpty / clear / getType / iteration
 * - O(n): toArray
 *
 * @template T_Value
 * @implements \Iterator<int, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class CircularBuffer implements \Iterator, \Countable, ToArray {

    /** @var array<int, T_Value> Slot index → value. Slots outside $count are stale. */
    private array $buffer = [];

    private int $head = 0;
    /** @var int<0, max> */
    private int $count = 0;
    private int $iterPos = 0;

    /**
     * @param int $capacity Maximum number of items the buffer can hold (must be positive).
     * @param string $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param iterable<T_Value> $items Initial items pushed in order; pushing past $capacity evicts the oldest.
     * @throws InvalidArgumentException When $capacity is not positive, or any item does not satisfy $type.
     */
    protected function __construct(
        private int $capacity,
        private string $type = 'mixed',
        iterable $items = []
    ) {
        if ($capacity < 1) {
            throw new InvalidArgumentException('Capacity must be a positive integer.');
        }
        foreach ($items as $item) {
            $this->push($item);
        }
    }

    /**
     * Factory for an untyped buffer (no runtime type enforcement).
     *
     * @param int $capacity Maximum number of items the buffer can hold (must be positive).
     * @param iterable<mixed> $items Initial items pushed in order; pushing past $capacity evicts the oldest.
     * @return self<mixed>
     * @throws \InvalidArgumentException When $capacity is not positive.
     */
    public static function any(int $capacity, iterable $items = []): self {
        return new self($capacity, 'mixed', $items);
    }

    /**
     * Typed factory for class instances. Unlike the constructor, the item
     * type is inferred statically: `CircularBuffer::of(3, Foo::class)` is
     * `CircularBuffer<Foo>` in both PHPStan and IDE analysis.
     *
     * @template T of object
     * @param int $capacity Maximum number of items the buffer can hold (must be positive).
     * @param class-string<T> $class Class to enforce on items.
     * @param iterable<T> $items Initial items pushed in order; pushing past $capacity evicts the oldest.
     * @return self<T>
     * @throws InvalidArgumentException When $capacity is not positive or any item does not satisfy $class.
     */
    public static function of(int $capacity, string $class, iterable $items = []): self {
        return new self($capacity, $class, $items);
    }

    /**
     * Get the configured type of this buffer. Complexity: O(1).
     * @return class-string<T_Value>|string
     */
    public function getType(): string {
        return $this->type;
    }

    /** Maximum number of items the buffer can hold. Complexity: O(1). */
    public function capacity(): int {
        return $this->capacity;
    }

    /** Whether the buffer is at capacity. The next push will evict the oldest item. Complexity: O(1). */
    public function isFull(): bool {
        return $this->count === $this->capacity;
    }

    /**
     * Append an item. When the buffer is full, the oldest item is evicted and
     * returned; otherwise returns null.
     *
     * Complexity: O(1).
     *
     * @param T_Value $item
     * @return T_Value|null Evicted item, or null if the buffer had room.
     * @throws InvalidArgumentException When $item does not satisfy $type.
     */
    public function push(mixed $item): mixed {
        ValidatesType::checkType($this->type, $item);
        $evicted = null;
        if ($this->count === $this->capacity) {
            $evicted = $this->buffer[$this->head];
            $this->buffer[$this->head] = $item;
            $this->head = ($this->head + 1) % $this->capacity;
        } else {
            $writeAt = ($this->head + $this->count) % $this->capacity;
            $this->buffer[$writeAt] = $item;
            $this->count++;
        }
        return $evicted;
    }

    /**
     * Remove and return the oldest item, or null if empty. Complexity: O(1).
     *
     * @return T_Value|null
     */
    public function pop(): mixed {
        if ($this->count === 0) {
            return null;
        }
        $value = $this->buffer[$this->head];
        unset($this->buffer[$this->head]);
        $this->head = ($this->head + 1) % $this->capacity;
        $this->count--;
        return $value;
    }

    /**
     * Return the oldest item without removing it, or null if empty. Complexity: O(1).
     *
     * @return T_Value|null
     */
    public function peek(): mixed {
        return $this->count === 0 ? null : $this->buffer[$this->head];
    }

    /** Number of items currently in the buffer. Complexity: O(1). */
    public function count(): int {
        return $this->count;
    }

    /** Whether the buffer holds no items. Complexity: O(1). */
    public function isEmpty(): bool {
        return $this->count === 0;
    }

    /** Discard all items and reset iteration state. Complexity: O(1). */
    public function clear(): void {
        $this->buffer = [];
        $this->head = 0;
        $this->count = 0;
        $this->iterPos = 0;
    }

    /** @return T_Value|null Item at the current iteration position (oldest to newest), or null past the end. Complexity: O(1). */
    public function current(): mixed {
        if ($this->iterPos >= $this->count) {
            return null;
        }
        return $this->buffer[($this->head + $this->iterPos) % $this->capacity];
    }

    /** Zero-based offset from the oldest item. Complexity: O(1). */
    public function key(): int {
        return $this->iterPos;
    }

    /** Advance the iteration position one step toward the newest item. Complexity: O(1). */
    public function next(): void {
        $this->iterPos++;
    }

    /** Reset the iteration position to the oldest item. Complexity: O(1). */
    public function rewind(): void {
        $this->iterPos = 0;
    }

    /** Whether the iteration position still points at a valid item. Complexity: O(1). */
    public function valid(): bool {
        return $this->iterPos < $this->count;
    }

    /** @return list<T_Value> Items from oldest to newest. Complexity: O(n). */
    public function toArray(): array {
        $out = [];
        for ($i = 0; $i < $this->count; $i++) {
            $out[] = $this->buffer[($this->head + $i) % $this->capacity];
        }
        return $out;
    }
}
