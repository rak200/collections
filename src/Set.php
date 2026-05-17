<?php

declare(strict_types=1);

namespace Rak200\Collections;

use Rak200\Caster\Contracts\ToArray;
use InvalidArgumentException;

/**
 * Unique-element set of objects, identified by {@see spl_object_id()}.
 *
 * Two distinct object instances are considered different members even if they
 * are structurally equal; identity is by object id, not value.
 *
 * @template T_Object of object
 * @implements \Iterator<int, T_Object>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Set implements \Iterator, \Countable, ToArray {

    /** @var array<int, T_Object> Indexed by spl_object_id. */
    private array $items = [];

    /**
     * @param class-string<T_Object>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param iterable<T_Object> $items Initial items added in order; duplicates are ignored.
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(private string $type = 'mixed', iterable $items = []) {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    /**
     * Get the configured type of this set.
     * @return class-string<T_Object>|string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @param T_Object $item
     * @throws InvalidArgumentException
     */
    private function checkType(object $item): void {
        if ($this->type === 'mixed') {
            return;
        }
        if (!is_a($item, $this->type, true)) {
            throw new InvalidArgumentException(sprintf(
                'Item must be an instance of %s. Got: %s',
                $this->type,
                $item::class
            ));
        }
    }

    /**
     * Add an item. Returns true if newly added, false if already present.
     *
     * @param T_Object $item
     * @throws InvalidArgumentException
     */
    public function add(object $item): bool {
        $this->checkType($item);
        $id = spl_object_id($item);
        if (isset($this->items[$id])) {
            return false;
        }
        $this->items[$id] = $item;
        return true;
    }

    /**
     * Remove an item. Returns true if it was present, false otherwise.
     *
     * @param T_Object $item
     */
    public function remove(object $item): bool {
        $id = spl_object_id($item);
        if (!isset($this->items[$id])) {
            return false;
        }
        unset($this->items[$id]);
        return true;
    }

    /** @param T_Object $item */
    public function contains(object $item): bool {
        return isset($this->items[spl_object_id($item)]);
    }

    public function count(): int {
        return count($this->items);
    }

    /** @return T_Object */
    public function current(): object {
        return current($this->items);
    }

    public function key(): int {
        return key($this->items);
    }

    public function next(): void {
        next($this->items);
    }

    public function rewind(): void {
        reset($this->items);
    }

    public function valid(): bool {
        return key($this->items) !== null;
    }

    /**
     * Return the items as a zero-indexed array (spl_object_id keys discarded).
     *
     * @return T_Object[]
     */
    public function toArray(): array {
        return array_values($this->items);
    }
}
