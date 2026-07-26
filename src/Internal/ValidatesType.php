<?php

declare(strict_types=1);

namespace Rak200\Collections\Internal;

use InvalidArgumentException;
use Rak200\Collections\AbstractCollection;
use Rak200\Collections\BiMap;
use Rak200\Collections\LinkedList;
use Rak200\Collections\Map;
use Rak200\Collections\ObjectMap;
use Rak200\Collections\PriorityQueue;
use Rak200\Utils\Type;

use function is_a;

/**
 * Static helpers for validating values against a configured `$type` discriminator.
 *
 * `$type` is one of:
 * - `'mixed'` — no check (any value passes)
 * - `'object'` — any object ({@see Type::isObject()})
 * - a pseudo-type for a PHP built-in: `'int'`/`'integer'`, `'string'`,
 *   `'bool'`/`'boolean'`, `'float'`/`'double'`, `'array'`, `'iterable'`,
 *   `'callable'` — checked with the matching `Type::is*()` predicate
 * - any other string — treated as a class-string; checked with `is_a()`
 *
 * The class-string arm keeps the native `is_a()` rather than utils'
 * {@see Type::isInstance()} / {@see Type::isA()}: both declare a
 * `class-string<T>` parameter, while `$type` here is an unconstrained string
 * validated at runtime — adopting them would only trade a native call for a
 * PHPStan suppression.
 *
 * Callers pass their own type as the first argument; the helper has no
 * knowledge of the containing class or its property layout.
 *
 * Used across the library by {@see AbstractCollection}
 * subclasses, {@see LinkedList},
 * {@see PriorityQueue}, {@see BiMap},
 * {@see Map}, and {@see ObjectMap}.
 *
 * @internal not part of the public API; subject to change
 *
 * @author rak200 <rak.ricardo@windowslive.com>
 */
abstract class ValidatesType
{
    /**
     * Throw if $value does not satisfy $type.
     *
     * See the class docblock for the full list of accepted `$type` strings.
     * The `$label` is interpolated into the error message so callers can
     * distinguish between items, keys, and values.
     *
     * @param string $type  a class-string or one of the pseudo-type discriminators listed in the class docblock
     * @param string $label Used in the error message (e.g. `'Item'`, `'Key'`, `'Value'`).
     *
     * @throws InvalidArgumentException when $value does not satisfy $type
     */
    public static function checkType(string $type, mixed $value, string $label = 'Item'): void
    {
        $valid = match ($type) {
            'mixed' => true,
            'object' => Type::isObject($value),
            'int', 'integer' => Type::isInt($value),
            'string' => Type::isStr($value),
            'bool', 'boolean' => Type::isBool($value),
            'float', 'double' => Type::isFloat($value),
            'array' => Type::isArray($value),
            'iterable' => Type::isIterable($value),
            'callable' => Type::isCallable($value),
            // 'null' => $value === null, // 'null' is not a valid type for collection items, keys, or values, so we don't support it as a type discriminator. If we did, this would be the check.
            default => Type::isObject($value) && is_a($value, $type),
        };

        if (!$valid) {
            throw new InvalidArgumentException("{$label} must be an instance of {$type}. Got: " . Type::of($value));
        }
    }
}
