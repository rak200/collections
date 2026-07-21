<?php

declare(strict_types=1);

namespace Rak200\Collections\Internal;

use Exception;
use InvalidArgumentException;

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

/**
 * Hybrid hashing for collections that need a uniqueness handle for arbitrary
 * values:
 * - objects: by {@see spl_object_id()} (identity)
 * - scalars and null: by value (so `"foo"` equals `"foo"` even across calls)
 * - arrays: by md5 of `serialize()` (structural equality).
 *
 * The returned string is prefixed by a type tag (`o:`, `i:`, `s:`, etc.) so
 * values of different types never collide (e.g. `'1'` and `1`).
 *
 * @internal not part of the public API; subject to change
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
trait HashesValues
{
    /**
     * Compute a uniqueness handle for any value.
     *
     * @throws InvalidArgumentException When the value type cannot be hashed (e.g. resource).
     */
    private static function hashValue(mixed $value): string
    {
        return match (true) {
            is_object($value) => 'o:' . spl_object_id($value),
            is_int($value) => 'i:' . $value,
            is_string($value) => 's:' . $value,
            is_bool($value) => 'b:' . ($value ? '1' : '0'),
            is_null($value) => 'n:',
            is_float($value) => 'f:' . var_export($value, true),
            is_array($value) => 'a:' . self::serializeArray($value),
            default => throw new InvalidArgumentException(
                'Unsupported value type: ' . get_debug_type($value)
            ),
        };
    }

    /**
     * Serialize an array for hashing. Uses `serialize()` internally, but catches
     * exceptions to provide a clearer error message when the array contains
     * unserializable values (e.g. resources).
     *
     * @param array<array-key, mixed> $array
     *
     * @return string hash of the array contents
     *
     * @throws InvalidArgumentException when the array cannot be serialized
     */
    private static function serializeArray(array $array): string
    {
        try {
            // Test if the array is serializable (e.g. doesn't contain resources)
            return md5(serialize($array));
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                'Cannot hash array: ' . $e->getMessage()
            );
        }
    }
}
