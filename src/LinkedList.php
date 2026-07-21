<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Countable;
use InvalidArgumentException;
use Iterator;
use Rak200\Caster\Contracts\ToArray;
use Rak200\Collections\Internal\LinkedNode;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;

/**
 * Doubly linked list of typed values.
 *
 * Mutating methods (`push`/`unshift`/`insertBefore`/`insertAfter`) return the
 * {@see LinkedNode} they created so callers can hold a handle for later
 * insertion or removal at that position. Values can be of any type; when a
 * class-string is given as the type, every value must be an instance of that
 * class.
 *
 * Iteration state ($cursor/$position) is held on the instance, so nested
 * `foreach` loops over the same list interfere with each other. Iterate a
 * snapshot via `toArray()` if concurrent traversal is needed.
 *
 * Common cases: LRU caches and free lists (holding the node lets you splice
 * any element out in constant time), playlists / undo-redo chains, and the
 * backing store for the higher-level {@see Queue} and {@see Deque}.
 *
 * Complexity:
 * - O(1): push / unshift / pop / shift / insertBefore / insertAfter / remove / head / tail / getType / count / isEmpty / clear / iteration
 * - O(n): fromVector / toArray
 *
 * @template T_Value
 *
 * @implements Iterator<int, T_Value>
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class LinkedList implements Iterator, Countable, ToArray
{
    use ProvidesValueFactories;

    /** @var null|LinkedNode<T_Value> */
    private ?LinkedNode $head = null;

    /** @var null|LinkedNode<T_Value> */
    private ?LinkedNode $tail = null;

    /** @var null|LinkedNode<T_Value> */
    private ?LinkedNode $cursor = null;

    private int $position = 0;

    /** @var int<0, max> */
    private int $count = 0;

    /**
     * @deprecated soft-deprecated in 0.5.0 — prefer the static factories ({@see self::of()}, {@see self::ofInt()}, {@see self::any()}, …). Stays public because this collection is composed by others; will be revisited in 1.0.0.
     *
     * @param string            $type  class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip
     * @param iterable<T_Value> $items initial items appended in order
     *
     * @throws InvalidArgumentException when any item does not satisfy $type
     */
    public function __construct(private string $type = 'mixed', iterable $items = [])
    {
        foreach ($items as $item) {
            $this->push($item);
        }
    }

    /**
     * Typed factory for class instances. Unlike the constructor, the item
     * type is inferred statically: `LinkedList::of(Foo::class)` is `LinkedList<Foo>`
     * in both PHPStan and IDE analysis.
     *
     * @template T of object
     *
     * @param class-string<T> $class class to enforce on items
     * @param iterable<T>     $items initial items appended in order
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
     * Build a new list from a {@see Vector}, preserving its type and order.
     *
     * Complexity: O(n) in the size of the vector.
     *
     * @param Vector<T_Value> $vector
     *
     * @return self<T_Value>
     */
    public static function fromVector(Vector $vector): self
    {
        return new self($vector->getType(), $vector->toArray());
    }

    /**
     * Get the configured type of this list. Complexity: O(1).
     *
     * @return class-string<T_Value>|string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Append at the tail. Complexity: O(1).
     *
     * @param T_Value $item
     *
     * @return LinkedNode<T_Value>
     *
     * @throws InvalidArgumentException
     */
    public function push(mixed $item): LinkedNode
    {
        ValidatesType::checkType($this->type, $item);
        $node = new LinkedNode($this, $item, prev: $this->tail);
        if ($this->tail === null) {
            $this->head = $node;
        } else {
            $this->tail->next = $node;
        }
        $this->tail = $node;
        ++$this->count;

        return $node;
    }

    /**
     * Prepend at the head. Complexity: O(1).
     *
     * @param T_Value $item
     *
     * @return LinkedNode<T_Value>
     *
     * @throws InvalidArgumentException
     */
    public function unshift(mixed $item): LinkedNode
    {
        ValidatesType::checkType($this->type, $item);
        $node = new LinkedNode($this, $item, next: $this->head);
        if ($this->head === null) {
            $this->tail = $node;
        } else {
            $this->head->prev = $node;
        }
        $this->head = $node;
        ++$this->count;

        return $node;
    }

    /**
     * Remove and return the tail value, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function pop(): mixed
    {
        if ($this->tail === null) {
            return null;
        }
        $node = $this->tail;
        $this->remove($node);

        return $node->value;
    }

    /**
     * Remove and return the head value, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function shift(): mixed
    {
        if ($this->head === null) {
            return null;
        }
        $node = $this->head;
        $this->remove($node);

        return $node->value;
    }

    /**
     * Insert a new node immediately before the given node. Complexity: O(1).
     *
     * @param LinkedNode<T_Value> $node
     * @param T_Value             $item
     *
     * @return LinkedNode<T_Value>
     *
     * @throws InvalidArgumentException
     */
    public function insertBefore(LinkedNode $node, mixed $item): LinkedNode
    {
        ValidatesType::checkType($this->type, $item);
        $new = new LinkedNode($this, $item, prev: $node->prev, next: $node);
        if ($node->prev !== null) {
            $node->prev->next = $new;
        } else {
            $this->head = $new;
        }
        $node->prev = $new;
        ++$this->count;

        return $new;
    }

    /**
     * Insert a new node immediately after the given node. Complexity: O(1).
     *
     * @param LinkedNode<T_Value> $node
     * @param T_Value             $item
     *
     * @return LinkedNode<T_Value>
     *
     * @throws InvalidArgumentException
     */
    public function insertAfter(LinkedNode $node, mixed $item): LinkedNode
    {
        ValidatesType::checkType($this->type, $item);
        $new = new LinkedNode($this, $item, prev: $node, next: $node->next);
        if ($node->next !== null) {
            $node->next->prev = $new;
        } else {
            $this->tail = $new;
        }
        $node->next = $new;
        ++$this->count;

        return $new;
    }

    /**
     * Unlink the given node from the list. Caller must pass a node that
     * belongs to this list; passing a foreign node corrupts both lists.
     *
     * Complexity: O(1) — the node handle removes the need to search.
     *
     * @param LinkedNode<T_Value> $node
     */
    public function remove(LinkedNode $node): void
    {
        if ($node->owner !== $this) {
            throw new InvalidArgumentException('Node does not belong to this list.');
        }

        if ($node->prev !== null) {
            $node->prev->next = $node->next;
        } else {
            $this->head = $node->next;
        }
        if ($node->next !== null) {
            $node->next->prev = $node->prev;
        } else {
            $this->tail = $node->prev;
        }
        $node->prev = null;
        $node->next = null;
        $this->count = max(0, $this->count - 1);
    }

    /**
     * Node at the head of the list, or null if empty. Complexity: O(1).
     *
     * @return null|LinkedNode<T_Value>
     */
    public function head(): ?LinkedNode
    {
        return $this->head;
    }

    /**
     * Node at the tail of the list, or null if empty. Complexity: O(1).
     *
     * @return null|LinkedNode<T_Value>
     */
    public function tail(): ?LinkedNode
    {
        return $this->tail;
    }

    /** Number of nodes currently stored. Complexity: O(1). */
    public function count(): int
    {
        return $this->count;
    }

    /** Whether the list has no nodes. Complexity: O(1). */
    public function isEmpty(): bool
    {
        return $this->head === null;
    }

    /** Discard all nodes and reset the iteration cursor. Complexity: O(1). */
    public function clear(): void
    {
        $this->head = null;
        $this->tail = null;
        $this->cursor = null;
        $this->position = 0;
        $this->count = 0;
    }

    /** @return null|T_Value Value at the current iteration cursor, or null past the end. Complexity: O(1). */
    public function current(): mixed
    {
        return $this->cursor?->value;
    }

    /** Zero-based offset from the head of the list. Complexity: O(1). */
    public function key(): int
    {
        return $this->position;
    }

    /** Advance the iteration cursor one node toward the tail. Complexity: O(1). */
    public function next(): void
    {
        $this->cursor = $this->cursor?->next;
        ++$this->position;
    }

    /** Reset the iteration cursor to the head of the list. Complexity: O(1). */
    public function rewind(): void
    {
        $this->cursor = $this->head;
        $this->position = 0;
    }

    /** Whether the iteration cursor still points at a valid node. Complexity: O(1). */
    public function valid(): bool
    {
        return $this->cursor !== null;
    }

    /** @return T_Value[] Values from head to tail. Complexity: O(n). */
    public function toArray(): array
    {
        $result = [];
        for ($node = $this->head; $node !== null; $node = $node->next) {
            $result[] = $node->value;
        }

        return $result;
    }
}
