<?php

declare(strict_types=1);

namespace Rak200\Collections;

use InvalidArgumentException;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;

use function array_pop;
use function count;

/**
 * LIFO stack. Iteration yields elements from top (most recently pushed) to bottom.
 *
 * Iteration state ($position) is held on the instance, so nested `foreach`
 * loops over the same stack interfere with each other. Iterate a snapshot via
 * `toArray()` if concurrent traversal is needed.
 *
 * Common cases: undo / redo stacks, DFS / backtracking traversals, parser
 * scopes, expression evaluation, function-call frames.
 *
 * Complexity:
 * - O(1): push / pop / peek / getType / count / isEmpty / clear / toArray / iteration
 *
 * @template T_Value
 *
 * @extends AbstractCollection<T_Value>
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Stack extends AbstractCollection
{
    use ProvidesValueFactories;

    private int $position = 0;

    /**
     * @param string            $type  class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip
     * @param iterable<T_Value> $items initial items pushed in order (last becomes top)
     *
     * @throws InvalidArgumentException when any item does not satisfy $type
     */
    protected function __construct(string $type = 'mixed', iterable $items = [])
    {
        parent::__construct($type);
        foreach ($items as $item) {
            $this->push($item);
        }
    }

    /**
     * Typed factory for class instances. Unlike the constructor, the item
     * type is inferred statically: `Stack::of(Foo::class)` is `Stack<Foo>`
     * in both PHPStan and IDE analysis.
     *
     * @template T of object
     *
     * @param class-string<T> $class class to enforce on items
     * @param iterable<T>     $items initial items pushed in order (last becomes top)
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
     * Push onto the top. Complexity: O(1).
     *
     * @param T_Value $item
     *
     * @throws InvalidArgumentException
     */
    public function push(mixed $item): void
    {
        ValidatesType::checkType($this->type, $item);
        $this->items[] = $item;
    }

    /**
     * Remove and return the top, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    /**
     * Return the top without removing it, or null if empty. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function peek(): mixed
    {
        $count = count($this->items);

        return $count === 0 ? null : $this->items[$count - 1];
    }

    /** Discard all items and reset the iteration cursor. Complexity: O(1). */
    public function clear(): void
    {
        parent::clear();
        $this->position = 0;
    }

    /** @return T_Value Item at the current iteration position (top-to-bottom). Complexity: O(1). */
    public function current(): mixed
    {
        return $this->items[count($this->items) - 1 - $this->position];
    }

    /** Zero-based offset from the top of the stack. Complexity: O(1). */
    public function key(): int
    {
        return $this->position;
    }

    /** Advance the iteration cursor one step toward the bottom. Complexity: O(1). */
    public function next(): void
    {
        ++$this->position;
    }

    /** Reset the iteration cursor to the top of the stack. Complexity: O(1). */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /** Whether the iteration cursor still points at a valid item. Complexity: O(1). */
    public function valid(): bool
    {
        return $this->position < count($this->items);
    }
}
