<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function get_debug_type;
use function is_int;
use function sprintf;
use InvalidArgumentException;
use Rak200\Collections\Internal\ProvidesValueFactories;
use Rak200\Collections\Internal\ValidatesType;

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
 * @template T_Value
 * @extends AbstractCollection<T_Value>
 * @implements \ArrayAccess<int, T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Vector extends AbstractCollection implements \ArrayAccess {

    use ProvidesValueFactories;

    /**
     * @param string $type Class name or built-in pseudo-type to enforce on items, or `'mixed'` to skip.
     * @param iterable<array-key, T_Value> $items Initial items; keys must be int (validated at runtime).
     * @throws InvalidArgumentException When any item does not satisfy $type, or any key is not an int.
     */
    protected function __construct(string $type = 'mixed', iterable $items = []) {
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
     * @param class-string<T> $class Class to enforce on items.
     * @param iterable<array-key, T> $items Initial items; keys must be int (validated at runtime).
     * @return self<T>
     * @throws InvalidArgumentException When any item does not satisfy $class, or any key is not an int.
     */
    public static function of(string $class, iterable $items = []): self {
        return new self($class, $items);
    }

    /** @return int|null Integer key at the current iteration cursor, or null past the end. */
    public function key(): ?int {
        $key = key($this->items);
        return is_int($key) ? $key : null;
    }

    /**
     * Whether the given offset is populated.
     *
     * @param int $offset
     */
    public function offsetExists(mixed $offset): bool {
        return isset($this->items[$offset]);
    }

    /**
     * Item at the given offset, or null if absent.
     *
     * @param int $offset
     * @return T_Value|null
     */
    public function offsetGet(mixed $offset): mixed {
        return $this->items[$offset] ?? null;
    }

    /**
     * Set the item at the given offset, or append when $offset is null.
     *
     * @param int|null $offset
     * @param T_Value $value
     * @throws InvalidArgumentException When $value is not an instance of $this->type.
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        ValidatesType::checkType($this->type, $value);
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Remove the item at the given offset (no-op if absent).
     *
     * @param int $offset
     */
    public function offsetUnset(mixed $offset): void {
        unset($this->items[$offset]);
    }

    /**
     * Set the item at the given offset, overwriting any existing entry.
     *
     * @param int $offset
     * @param T_Value $item
     * @throws InvalidArgumentException When $item is not an instance of $this->type.
     */
    public function add(int $offset, mixed $item): void {
        ValidatesType::checkType($this->type, $item);
        $this->items[$offset] = $item;
    }

    /**
     * Remove the item at the given offset (no-op if absent).
     *
     * @param int $offset
     */
    public function remove(int $offset): void {
        unset($this->items[$offset]);
    }

    /**
     * Item at the given offset, or null if absent.
     *
     * @param int $offset
     * @return T_Value|null
     */
    public function get(int $offset): mixed {
        return $this->items[$offset] ?? null;
    }
}
