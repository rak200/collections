<?php

declare(strict_types=1);

namespace Rak200\Collections;

use function trigger_error;
use InvalidArgumentException;

/**
 * @deprecated since 0.0.2, will be removed in 1.0.0. Use {@see Vector} for
 * int-keyed sequences or {@see Map} for keyed lookups.
 *
 * Backwards-compatible shim over {@see Vector} that also accepts string
 * keys via `add()`, `get()`, `remove()`, and ArrayAccess.
 *
 * @template T_Value
 * @extends Vector<T_Value>
 * @author rak200 <rak.ricardo@windowslive.com>
 */
class Collection extends Vector {

    /**
     * @param class-string<T_Value>|'mixed' $type Class name to enforce, or 'mixed' to skip type checking.
     * @param T_Value[] $items Initial items.
     * @throws InvalidArgumentException When any item is not an instance of $type.
     */
    public function __construct(string $type = 'mixed', array $items = []) {
        trigger_error(
            self::class . ' is deprecated since 0.0.2 and will be removed in 1.0.0. Use ' . Vector::class . ' or ' . Map::class . ' instead.',
            E_USER_DEPRECATED
        );
        parent::__construct($type, $items);
    }

    /**
     * Set the item at the given offset (int or string).
     *
     * @param T_Value $item
     * @throws InvalidArgumentException When $item is not an instance of $this->type.
     */
    public function add(int|string $offset, mixed $item): void {
        $this[$offset] = $item;
    }

    /**
     * Item at the given offset (int or string), or null if absent.
     *
     * @return T_Value|null
     */
    public function get(int|string $offset): mixed {
        return $this[$offset] ?? null;
    }

    /** Remove the item at the given offset (no-op if absent). */
    public function remove(int|string $offset): void {
        unset($this[$offset]);
    }
}
