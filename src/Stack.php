<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function array_pop, count;
use InvalidArgumentException;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;

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
 * @template T_Value
 * @extends AbstractCollection<T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Stack extends AbstractCollection {

    use ProvidesValueFactories;

    private int $position = 0;

    /**
     * @param string $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param iterable<T_Value> $items Initial items pushed in order (last becomes top).
     * @throws InvalidArgumentException When any item does not satisfy $type.
     */
    protected function __construct(string $type = 'mixed', iterable $items = []) {
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
     * @param class-string<T> $class Class to enforce on items.
     * @param iterable<T> $items Initial items pushed in order (last becomes top).
     * @return self<T>
     * @throws InvalidArgumentException When any item does not satisfy $class.
     */
    public static function of(string $class, iterable $items = []): self {
        return new self($class, $items);
    }

    /**
     * Push onto the top.
     *
     * @param T_Value $item
     * @throws InvalidArgumentException
     */
    public function push(mixed $item): void {
        ValidatesType::checkType($this->type, $item);
        $this->items[] = $item;
    }

    /**
     * Remove and return the top, or null if empty.
     *
     * @return T_Value|null
     */
    public function pop(): mixed {
        return array_pop($this->items);
    }

    /**
     * Return the top without removing it, or null if empty.
     *
     * @return T_Value|null
     */
    public function peek(): mixed {
        $count = count($this->items);
        return $count === 0 ? null : $this->items[$count - 1];
    }

    /** Discard all items and reset the iteration cursor. */
    public function clear(): void {
        parent::clear();
        $this->position = 0;
    }

    /** @return T_Value Item at the current iteration position (top-to-bottom). */
    public function current(): mixed {
        return $this->items[count($this->items) - 1 - $this->position];
    }

    /** Zero-based offset from the top of the stack. */
    public function key(): int {
        return $this->position;
    }

    /** Advance the iteration cursor one step toward the bottom. */
    public function next(): void {
        $this->position++;
    }

    /** Reset the iteration cursor to the top of the stack. */
    public function rewind(): void {
        $this->position = 0;
    }

    /** Whether the iteration cursor still points at a valid item. */
    public function valid(): bool {
        return $this->position < count($this->items);
    }
}
