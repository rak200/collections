<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use InvalidArgumentException;

/**
 * Doubly linked list with O(1) insertion and removal at any node.
 *
 * Holding a {@see DoublyLinkedListNode} reference (returned by push/unshift/
 * insertBefore/insertAfter) allows constant-time insertion or removal at
 * arbitrary positions. Values can be of any type; when a class-string is
 * given as the type, every value must be an instance of that class.
 *
 * @template T_Value
 * @implements \Iterator<int, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class DoublyLinkedList implements \Iterator, \Countable, ToArray {

    /** @var DoublyLinkedListNode<T_Value>|null */
    private ?DoublyLinkedListNode $head = null;

    /** @var DoublyLinkedListNode<T_Value>|null */
    private ?DoublyLinkedListNode $tail = null;

    /** @var DoublyLinkedListNode<T_Value>|null */
    private ?DoublyLinkedListNode $cursor = null;

    private int $position = 0;
    private int $count = 0;

    /**
     * @param class-string<T_Value&object>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param iterable<T_Value> $items Initial items appended in order.
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(private string $type = 'mixed', iterable $items = []) {
        foreach ($items as $item) {
            $this->push($item);
        }
    }

    public static function fromVector(Vector $vector): self {
        return new self($vector->getType(), $vector->toArray());
    }

    /**
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    private function checkType(mixed $item): void {
        if ($this->type === 'mixed') {
            return;
        }
        if (!($item instanceof $this->type)) {
            throw new InvalidArgumentException(sprintf(
                'Item must be an instance of %s. Got: %s',
                $this->type,
                get_debug_type($item)
            ));
        }
    }

    /**
     * Append at the tail.
     *
     * @param T_Value $item
     * @return DoublyLinkedListNode<T_Value>
     * @throws InvalidArgumentException
     */
    public function push(mixed $item): DoublyLinkedListNode {
        $this->checkType($item);
        $node = new DoublyLinkedListNode($item);
        if ($this->tail === null) {
            $this->head = $this->tail = $node;
        } else {
            $node->prev = $this->tail;
            $this->tail->next = $node;
            $this->tail = $node;
        }
        $this->count++;
        return $node;
    }

    /**
     * Prepend at the head.
     *
     * @param T_Value $item
     * @return DoublyLinkedListNode<T_Value>
     * @throws InvalidArgumentException
     */
    public function unshift(mixed $item): DoublyLinkedListNode {
        $this->checkType($item);
        $node = new DoublyLinkedListNode($item);
        if ($this->head === null) {
            $this->head = $this->tail = $node;
        } else {
            $node->next = $this->head;
            $this->head->prev = $node;
            $this->head = $node;
        }
        $this->count++;
        return $node;
    }

    /**
     * Remove and return the tail value, or null if empty.
     *
     * @return T_Value|null
     */
    public function pop(): mixed {
        if ($this->tail === null) {
            return null;
        }
        $node = $this->tail;
        $this->remove($node);
        return $node->value;
    }

    /**
     * Remove and return the head value, or null if empty.
     *
     * @return T_Value|null
     */
    public function shift(): mixed {
        if ($this->head === null) {
            return null;
        }
        $node = $this->head;
        $this->remove($node);
        return $node->value;
    }

    /**
     * Insert a new node immediately before the given node.
     *
     * @param DoublyLinkedListNode<T_Value> $node
     * @param T_Value $item
     * @return DoublyLinkedListNode<T_Value>
     * @throws InvalidArgumentException
     */
    public function insertBefore(DoublyLinkedListNode $node, mixed $item): DoublyLinkedListNode {
        $this->checkType($item);
        $new = new DoublyLinkedListNode($item);
        $new->next = $node;
        $new->prev = $node->prev;
        if ($node->prev !== null) {
            $node->prev->next = $new;
        } else {
            $this->head = $new;
        }
        $node->prev = $new;
        $this->count++;
        return $new;
    }

    /**
     * Insert a new node immediately after the given node.
     *
     * @param DoublyLinkedListNode<T_Value> $node
     * @param T_Value $item
     * @return DoublyLinkedListNode<T_Value>
     * @throws InvalidArgumentException
     */
    public function insertAfter(DoublyLinkedListNode $node, mixed $item): DoublyLinkedListNode {
        $this->checkType($item);
        $new = new DoublyLinkedListNode($item);
        $new->prev = $node;
        $new->next = $node->next;
        if ($node->next !== null) {
            $node->next->prev = $new;
        } else {
            $this->tail = $new;
        }
        $node->next = $new;
        $this->count++;
        return $new;
    }

    /**
     * Unlink the given node from the list. Caller must pass a node that
     * belongs to this list; passing a foreign node corrupts both lists.
     *
     * @param DoublyLinkedListNode<T_Value> $node
     */
    public function remove(DoublyLinkedListNode $node): void {
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
        $this->count--;
    }

    /** @return DoublyLinkedListNode<T_Value>|null */
    public function head(): ?DoublyLinkedListNode {
        return $this->head;
    }

    /** @return DoublyLinkedListNode<T_Value>|null */
    public function tail(): ?DoublyLinkedListNode {
        return $this->tail;
    }

    public function count(): int {
        return $this->count;
    }

    /** @return T_Value */
    public function current(): mixed {
        return $this->cursor->value;
    }

    public function key(): int {
        return $this->position;
    }

    public function next(): void {
        $this->cursor = $this->cursor?->next;
        $this->position++;
    }

    public function rewind(): void {
        $this->cursor = $this->head;
        $this->position = 0;
    }

    public function valid(): bool {
        return $this->cursor !== null;
    }

    /** @return T_Value[] */
    public function toArray(): array {
        $result = [];
        for ($node = $this->head; $node !== null; $node = $node->next) {
            $result[] = $node->value;
        }
        return $result;
    }
}
