<?php

declare(strict_types=1);

namespace Rak200\Collections\Internal;

use InvalidArgumentException;
use function get_debug_type, is_a, is_array, is_bool, is_callable, is_float, is_int, is_iterable, is_object, is_string;

/**
 * Static helpers for validating values against a configured `$type` discriminator.
 *
 * `$type` is one of:
 * - `'mixed'` — no check (any value passes)
 * - `'object'` — any object (`is_object`)
 * - a pseudo-type for a PHP built-in: `'int'`/`'integer'`, `'string'`,
 *   `'bool'`/`'boolean'`, `'float'`/`'double'`, `'array'`, `'iterable'`,
 *   `'callable'` — checked with the matching `is_*` function
 * - any other string — treated as a class-string; checked with `is_a()`
 *
 * Callers pass their own type as the first argument; the helper has no
 * knowledge of the containing class or its property layout.
 *
 * Used across the library by {@see \Rak200\Collections\AbstractCollection}
 * subclasses, {@see \Rak200\Collections\LinkedList},
 * {@see \Rak200\Collections\PriorityQueue}, {@see \Rak200\Collections\BiMap},
 * {@see \Rak200\Collections\Map}, and {@see \Rak200\Collections\ObjectMap}.
 *
 * @internal Not part of the public API; subject to change.
 */
abstract class ValidatesType {

    /**
     * Throw if $value does not satisfy $type.
     *
     * See the class docblock for the full list of accepted `$type` strings.
     * The `$label` is interpolated into the error message so callers can
     * distinguish between items, keys, and values.
     *
     * @param string $type A class-string or one of the pseudo-type discriminators listed in the class docblock.
     * @param string $label Used in the error message (e.g. `'Item'`, `'Key'`, `'Value'`).
     * @throws InvalidArgumentException When $value does not satisfy $type.
     */
    public static function checkType(string $type, mixed $value, string $label = 'Item'): void {
        $valid = match ($type) {
            'mixed' => true,
            'object' => is_object($value),
            'int', 'integer' => is_int($value),
            'string' => is_string($value),
            'bool', 'boolean' => is_bool($value),
            'float', 'double' => is_float($value),
            'array' => is_array($value),
            'iterable' => is_iterable($value),
            'callable' => is_callable($value),
            // 'null' => $value === null, // 'null' is not a valid type for collection items, keys, or values, so we don't support it as a type discriminator. If we did, this would be the check.
            default => is_object($value) && is_a($value, $type),
        };

        if (!$valid) {
            throw new InvalidArgumentException("{$label} must be an instance of {$type}. Got: " . get_debug_type($value));
        }
    }
}
