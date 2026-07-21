<?php

declare(strict_types=1);

namespace Rak200\Collections\Tests;

use ReflectionClass;

/**
 * Test-only escape hatch for constructing collections whose constructors are
 * `protected`, in the few cases with no public factory: the `'array'` /
 * `'iterable'` discriminators, pseudo-typed map values, and partially-typed
 * object maps. Everything else uses the public factories (`of()`, `ofInt()`,
 * `any()`, …).
 */
trait ConstructsProtected
{
    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected static function build(string $class, mixed ...$args): object
    {
        $reflection = new ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();
        // Run the (protected) constructor so its validation still fires.
        $reflection->getConstructor()?->invokeArgs($instance, $args);

        return $instance;
    }
}
