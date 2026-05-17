<?php

declare(strict_types=1);

namespace Rak200\Collections;

/**
 * Node in a {@see LinkedList}.
 *
 * @template T_Value
 * @author rak200 <rak.ricardo@windowslive.com>
 */
final class LinkedNode {

    /**
     * @var self<T_Value>|null
     * @internal Maintained by LinkedList; do not write directly.
     */
    public ?self $prev = null;

    /**
     * @var self<T_Value>|null
     * @internal Maintained by LinkedList; do not write directly.
     */
    public ?self $next = null;

    /** @param T_Value $value */
    public function __construct(public readonly mixed $value) {}
}
