<?php

declare(strict_types=1);

namespace Rak200\Collections\Internal;

use InvalidArgumentException;

/**
 * Pseudo-type static factories for single-value collections whose constructor
 * is `__construct(string $type, iterable $items = [])`.
 *
 * These replace direct `new` calls so the element type is inferred statically
 * by both PHPStan and IDE tooling (a plain `@return self<int>` is understood
 * everywhere, unlike a discriminator string passed to the constructor).
 *
 * The class-string factory `of()` is intentionally NOT here: binding a
 * per-call method template (`class-string<T>` → `self<T>`) does not resolve
 * through a trait in IDE analysis, so each class declares `of()` inline.
 *
 * @internal Used by the concrete collection classes; not a public contract.
 */
trait ProvidesValueFactories {

    /**
     * Untyped collection (no runtime type enforcement).
     *
     * @param iterable<array-key, mixed> $items
     * @return self<mixed>
     */
    public static function any(iterable $items = []): self {
        return new self('mixed', $items);
    }

    /**
     * Collection of any objects (`'object'` discriminator).
     *
     * @param iterable<array-key, object> $items
     * @return self<object>
     * @throws InvalidArgumentException When any item is not an object.
     */
    public static function ofObject(iterable $items = []): self {
        return new self('object', $items);
    }

    /**
     * @param iterable<array-key, mixed> $items
     * @return self<int>
     * @throws InvalidArgumentException When any item is not an int.
     */
    public static function ofInt(iterable $items = []): self {
        return new self('int', $items);
    }

    /**
     * @param iterable<array-key, mixed> $items
     * @return self<string>
     * @throws InvalidArgumentException When any item is not a string.
     */
    public static function ofString(iterable $items = []): self {
        return new self('string', $items);
    }

    /**
     * @param iterable<array-key, mixed> $items
     * @return self<bool>
     * @throws InvalidArgumentException When any item is not a bool.
     */
    public static function ofBool(iterable $items = []): self {
        return new self('bool', $items);
    }

    /**
     * @param iterable<array-key, mixed> $items
     * @return self<float>
     * @throws InvalidArgumentException When any item is not a float.
     */
    public static function ofFloat(iterable $items = []): self {
        return new self('float', $items);
    }

    /**
     * @param iterable<array-key, mixed> $items
     * @return self<callable>
     * @throws InvalidArgumentException When any item is not callable.
     */
    public static function ofCallable(iterable $items = []): self {
        return new self('callable', $items);
    }
}
