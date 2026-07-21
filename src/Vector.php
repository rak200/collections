<?php

declare(strict_types=1);

namespace Rak200\Collections;

use ArrayAccess;
use InvalidArgumentException;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;

use function get_debug_type;
use function is_int;
use function sprintf;

/**
 * Typed generic collection of mixed values, indexed by int.
 *
 * Implements `Iterator`, `ArrayAccess`, `Countable`, and `ToArray` (the first,
 * third, and fourth come from {@see AbstractCollection}). When a class-string
 * is given as the type, every item must be an instance of that class; with
 * 'mixed', any value is accepted.
 *
 * Common cases: ordered lists of typed items (DTO collections, search
 * results, paginated rows), int-indexed sequences where keyed lookup isn't
 * needed.
 *
 * Complexity:
 * - O(1): add / get / remove / offsetGet / offsetSet / offsetExists / offsetUnset / count / isEmpty / clear / toArray / iteration
 *
 * (For O(1) insertion or removal in the middle, reach for {@see LinkedList}.)
 *
 * @template T_Value
 *
 * @extends AbstractCollection<T_Value>
 *
 * @implements ArrayAccess<int, T_Value>
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Vector extends AbstractCollection implements ArrayAccess
{
    use ProvidesValueFactories;

    /**
     * @param string                       $type  class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip
     * @param iterable<array-key, T_Value> $items initial items; keys must be int (validated at runtime)
     *
     * @throws InvalidArgumentException when any item does not satisfy $type, or any key is not an int
     */
    protected function __construct(string $type = 'mixed', iterable $items = [])
    {
        parent::__construct($type);
        foreach ($items as $key => $item) {
            if (!is_int($key)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid key type: expected int, got %s',
                    get_debug_type($key)
                ));
            }
            ValidatesType::checkType($this->type, $item);
            $this->items[$key] = $item;
        }
    }

    /**
     * Typed factory for class instances. Unlike the constructor, the item
     * type is inferred statically: `Vector::of(Foo::class)` is `Vector<Foo>`
     * in both PHPStan and IDE analysis.
     *
     * @template T of object
     *
     * @param class-string<T>        $class class to enforce on items
     * @param iterable<array-key, T> $items initial items; keys must be int (validated at runtime)
     *
     * @return self<T>
     *
     * @throws InvalidArgumentException when any item does not satisfy $class, or any key is not an int
     */
    public static function of(string $class, iterable $items = []): self
    {
        return new self($class, $items);
    }

    /**
     * Integer key at the current iteration cursor, or null past the end. Complexity: O(1).
     */
    public function key(): ?int
    {
        $key = key($this->items);

        return is_int($key) ? $key : null;
    }

    /**
     * Whether the given offset is populated. Complexity: O(1).
     *
     * @param int $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Item at the given offset, or null if absent. Complexity: O(1).
     *
     * @param int $offset
     *
     * @return null|T_Value
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * Set the item at the given offset, or append when $offset is null. Complexity: O(1).
     *
     * @param null|int $offset
     * @param T_Value  $value
     *
     * @throws InvalidArgumentException when $value is not an instance of $this->type
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        ValidatesType::checkType($this->type, $value);
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Remove the item at the given offset (no-op if absent). Complexity: O(1).
     *
     * @param int $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Set the item at the given offset, overwriting any existing entry. Complexity: O(1).
     *
     * @param T_Value $item
     *
     * @throws InvalidArgumentException when $item is not an instance of $this->type
     */
    public function add(int $offset, mixed $item): void
    {
        ValidatesType::checkType($this->type, $item);
        $this->items[$offset] = $item;
    }

    /**
     * Remove the item at the given offset (no-op if absent). Complexity: O(1).
     */
    public function remove(int $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Item at the given offset, or null if absent. Complexity: O(1).
     *
     * @return null|T_Value
     */
    public function get(int $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }
}
