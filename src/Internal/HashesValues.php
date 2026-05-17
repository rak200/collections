<?php

declare(strict_types=1);

namespace Rak200\Collections\Internal;

use function get_debug_type;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_null;
use function is_object;
use function is_string;
use function md5;
use function serialize;
use InvalidArgumentException;

/**
 * Hybrid hashing for collections that need a uniqueness handle for arbitrary
 * values:
 * - objects: by {@see spl_object_id()} (identity)
 * - scalars and null: by value (so `"foo"` equals `"foo"` even across calls)
 * - arrays: by md5 of `serialize()` (structural equality)
 *
 * The returned string is prefixed by a type tag (`o:`, `i:`, `s:`, etc.) so
 * values of different types never collide (e.g. `'1'` and `1`).
 *
 * @internal Not part of the public API; subject to change.
 */
trait HashesValues {

    /**
     * Compute a uniqueness handle for any value.
     *
     * @throws InvalidArgumentException When the value type cannot be hashed (e.g. resource).
     */
    private static function hashValue(mixed $value): string {
        return match (true) {
            is_object($value) => 'o:' . spl_object_id($value),
            is_int($value)    => 'i:' . $value,
            is_string($value) => 's:' . $value,
            is_bool($value)   => 'b:' . ($value ? '1' : '0'),
            is_null($value)   => 'n:',
            is_float($value)  => 'f:' . var_export($value, true),
            is_array($value)  => 'a:' . md5(serialize($value)),
            default           => throw new InvalidArgumentException(
                'Unsupported value type: ' . get_debug_type($value)
            ),
        };
    }
}
